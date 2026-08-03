<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPackage;
use App\Models\CustomerPackageTransaction;
use App\Models\EmployeeAllocation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Repositories\CustomerPackageRepository;
use App\Repositories\InvoiceRepository;
use RuntimeException;
use Throwable;

/**
 * Billing engine: computes invoice figures, validates allocations,
 * persists invoices (with items, allocations, payments, ledger and
 * package deductions) and supports voiding with full reversal.
 */
final class BillingService
{
    public function __construct(
        private InvoiceRepository $invoices,
        private CustomerPackageRepository $customerPackages,
        private Invoice $invoiceModel,
        private InvoiceItem $itemModel,
        private EmployeeAllocation $allocationModel,
        private Payment $paymentModel,
        private LedgerEntry $ledgerModel,
        private CustomerPackage $packageModel,
        private CustomerPackageTransaction $packageTransactionModel,
        private Customer $customerModel,
        private ActivityService $activity
    ) {
    }

    /**
     * Validate employee allocations for a single line item.
     * Total allocation must exactly equal the line total.
     */
    public function validateAllocation(float $lineTotal, array $allocations): array
    {
        $errors = [];

        if ($allocations === []) {
            return ['valid' => true, 'errors' => []];
        }

        $sum = 0.0;
        foreach ($allocations as $a) {
            $amount = (float)($a['amount'] ?? 0);
            if ($amount <= 0) {
                $errors[] = 'Every employee allocation must be greater than zero.';
            }
            $sum += $amount;
        }

        $roundedSum = round($sum, 2);
        $roundedLine = round($lineTotal, 2);

        if (abs($roundedSum - $roundedLine) > 0.01) {
            $errors[] = sprintf(
                'Allocation total (%s) must equal the service total (%s).',
                number_format($roundedSum, 2),
                number_format($roundedLine, 2)
            );
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * Build a full invoice calculation from raw payload.
     * Shared by save() and the client-side preview endpoint.
     */
    public function compute(array $payload): array
    {
        $items = $payload['items'] ?? [];
        $lineItems = [];

        $subtotal = 0.0;
        foreach ($items as $item) {
            $price = (float)($item['price'] ?? 0);
            $qty   = max(1, (int)($item['qty'] ?? 1));
            $total = round($price * $qty, 2);
            $subtotal += $total;
            $lineItems[] = [
                'service_id'    => !empty($item['service_id']) ? (int)$item['service_id'] : null,
                'service_name'  => trim((string)($item['name'] ?? '')),
                'price'         => $price,
                'qty'           => $qty,
                'total'         => $total,
                'allocations'   => $item['allocations'] ?? [],
            ];
        }

        $subtotal = round($subtotal, 2);

        // No GST / discount — plain total from wallet balance only.
        $total = $subtotal;

        $packageUsage = $payload['package_usage'] ?? [];
        if (!is_array($packageUsage)) {
            $packageUsage = [];
        }
        $requestedAmount = (float)($packageUsage['amount'] ?? $payload['package_used'] ?? 0);
        $packageCustomerId = (int)($packageUsage['customer_id'] ?? $payload['customer_id'] ?? 0);

        // Wallet (package) balance available, oldest package consumed first.
        $active = $packageCustomerId > 0 ? $this->customerPackages->activeFor($packageCustomerId) : [];
        $availableBalance = 0.0;
        foreach ($active as $row) {
            $vpc = (float) $row['value_per_credit'];
            if ($vpc <= 0 && (int) $row['credits'] > 0) {
                $vpc = (float) $row['selling_price'] / (int) $row['credits'];
            }
            $availableBalance += (float) $row['remaining_credits'] * $vpc;
        }
        $availableBalance = round($availableBalance, 2);

        // Deduct from active packages (oldest first) up to the bill total.
        // No overrun: the deduction never exceeds the available balance, so
        // the wallet never goes negative. A shortfall must be covered by a
        // top-up package before the bill is accepted.
        $packageDeduction = 0.0;
        $packageId = null;
        $packageCreditsUsed = 0;
        $deductions = [];
        $toUse = min($requestedAmount, $total);
        if ($toUse > 0) {
            $remaining = $toUse;
            foreach (array_reverse($active) as $row) {
                if ($remaining <= 0.001) {
                    break;
                }
                $vpc = (float) $row['value_per_credit'];
                if ($vpc <= 0 && (int) $row['credits'] > 0) {
                    $vpc = (float) $row['selling_price'] / (int) $row['credits'];
                }
                if ($vpc <= 0) {
                    continue;
                }
                $available = round((float) $row['remaining_credits'] * $vpc, 2);
                if ($available <= 0.001) {
                    continue;
                }
                $take = min($remaining, $available);
                $creditsUsed = (int) ceil($take / $vpc);
                $creditsUsed = min($creditsUsed, (int) $row['remaining_credits']);
                if ($creditsUsed <= 0) {
                    continue;
                }
                $deduct = round($creditsUsed * $vpc, 2);
                if ($deduct > $remaining) {
                    $deduct = round($remaining, 2);
                }
                $deductions[] = [
                    'package_id' => (int) $row['id'],
                    'credits'    => $creditsUsed,
                    'amount'     => $deduct,
                ];
                $packageDeduction += $deduct;
                $packageCreditsUsed += $creditsUsed;
                $remaining = round($remaining - $deduct, 2);
            }
            $packageId = $deductions[0]['package_id'] ?? null;
        }

        $packageDeduction = round(min($packageDeduction, $total), 2);
        $amountPayable = round($total - $packageDeduction, 2);

        // Wallet-only bill: no cash/card/upi payment component.
        $payments = [];
        $amountPaid = 0.0;
        $dueAmount = round($amountPayable, 2);

        $paymentStatus = $dueAmount <= 0.001 ? 'paid' : 'unpaid';

        return [
            'items'             => $lineItems,
            'subtotal'          => $subtotal,
            'discount_type'     => 'flat',
            'discount_value'    => 0.0,
            'discount_amount'   => 0.0,
            'gst_rate'          => 0.0,
            'gst_amount'        => 0.0,
            'total'             => $total,
            'package_deduction' => $packageDeduction,
            'package_id'        => $packageId,
            'package_credits'   => $packageCreditsUsed,
            'package_deductions'=> $deductions,
            'amount_payable'    => $amountPayable,
            'amount_paid'       => $amountPaid,
            'due_amount'        => $dueAmount,
            'payment_status'    => $paymentStatus,
            'payments'          => $payments,
            'available_balance' => $availableBalance,
            'shortfall'         => round(max(0, $total - $availableBalance), 2),
        ];
    }

    /**
     * Create a lifetime wallet top-up package for a customer.
     * Credits map 1:1 to rupees (value_per_credit = 1.00), so the balance
     * value equals the amount paid. No expiry.
     */
    public function createTopUpPackage(int $customerId, float $amount, string $name = 'Wallet Top-up'): int
    {
        $credits = max(1, (int) round($amount));
        $id = (int) $this->packageModel->create([
            'customer_id'        => $customerId,
            'package_id'         => null,
            'name'               => $name,
            'selling_price'      => $amount,
            'credits'            => $credits,
            'remaining_credits'  => $credits,
            'value_per_credit'   => 1.00,
            'validity_days'      => null,
            'starts_on'          => date('Y-m-d'),
            'expires_on'         => null,
            'status'             => 'active',
            'notes'              => 'Wallet top-up (lifetime)',
        ]);

        $this->packageTransactionModel->create([
            'customer_package_id' => $id,
            'customer_id'         => $customerId,
            'type'                => 'purchase',
            'credits'             => $credits,
            'amount'              => $amount,
            'description'         => $name,
        ]);

        $this->activity->log('package.topup', 'customer_package', $id, [
            'customer_id' => $customerId,
            'amount'      => $amount,
        ]);

        return $id;
    }

    /**
     * Persist an invoice. $payload matches the billing POS form.
     *
     * @param array $payload
     * @param string $mode 'final' | 'draft'
     */
    public function createInvoice(array $payload, string $mode = 'final'): int
    {
        $customerId = (int)($payload['customer_id'] ?? 0);
        if ($customerId <= 0) {
            throw new RuntimeException('A customer is required to create a bill.');
        }

        // Allow the POS to attach a wallet top-up in the same request so the
        // bill never has to be blocked. The top-up is a lifetime custom package.
        $topUp = (float)($payload['top_up'] ?? 0);
        if ($topUp > 0) {
            $this->createTopUpPackage($customerId, $topUp, (string)($payload['top_up_name'] ?? 'Wallet Top-up'));
        }

        $calculation = $this->compute($payload);

        if (empty($calculation['items'])) {
            throw new RuntimeException('Add at least one service to the bill.');
        }

        // No negative balances: the wallet must cover the full bill. The POS
        // attaches a top-up package to satisfy this before submitting.
        if ($mode === 'final' && $calculation['due_amount'] > 0.001) {
            throw new RuntimeException(sprintf(
                'Insufficient wallet balance. Add a top-up of ₹%s to continue.',
                number_format($calculation['shortfall'], 2)
            ));
        }

        // Allocation validation (server side)
        foreach ($calculation['items'] as $item) {
            $result = $this->validateAllocation($item['total'], $item['allocations']);
            if (!$result['valid']) {
                throw new RuntimeException(implode(' ', $result['errors']));
            }
        }

        $invoiceNumber = $this->invoices->nextInvoiceNumber();

        return \App\Core\App::getInstance()->db->transaction(function () use (
            $payload,
            $calculation,
            $customerId,
            $invoiceNumber,
            $mode
        ) {
            $status = 'draft';
            if ($mode === 'final') {
                $status = match (true) {
                    $calculation['due_amount'] <= 0.001 => 'paid',
                    $calculation['amount_paid'] > 0     => 'partially_paid',
                    default                             => 'issued',
                };
            }

            $invoiceId = $this->invoiceModel->create([
                'invoice_number' => $invoiceNumber,
                'customer_id'    => $customerId,
                'subtotal'       => $calculation['subtotal'],
                'discount'       => $calculation['discount_amount'],
                'gst_percent'    => $calculation['gst_rate'],
                'gst_amount'     => $calculation['gst_amount'],
                'total'          => $calculation['total'],
                'package_used'   => $calculation['package_deduction'],
                'payable'        => $calculation['amount_payable'],
                'paid'           => $mode === 'final' ? $calculation['amount_paid'] : 0.00,
                'balance'        => $mode === 'final' ? $calculation['due_amount'] : $calculation['amount_payable'],
                'notes'          => $payload['notes'] ?? null,
                'status'         => $status,
                'invoice_date'   => date('Y-m-d'),
                'due_date'       => null,
                'created_by'     => auth_id(),
            ]);

            // Items + allocations
            foreach ($calculation['items'] as $item) {
                $itemId = $this->itemModel->create([
                    'invoice_id'   => $invoiceId,
                    'service_id'   => $item['service_id'],
                    'description'  => $item['service_name'] !== '' ? $item['service_name'] : 'Custom service',
                    'price'        => $item['price'],
                    'qty'          => $item['qty'],
                    'amount'       => $item['total'],
                ]);

                foreach ($item['allocations'] as $alloc) {
                    $employeeId = (int)($alloc['employee_id'] ?? 0);
                    $amount = (float)($alloc['amount'] ?? 0);
                    if ($employeeId <= 0 || $amount <= 0) {
                        continue;
                    }
                    $this->allocationModel->create([
                        'invoice_id'      => $invoiceId,
                        'invoice_item_id' => $itemId,
                        'employee_id'     => $employeeId,
                        'amount'          => $amount,
                    ]);
                }
            }

            if ($mode === 'draft') {
                $this->activity->log('invoice.draft_created', 'invoice', $invoiceId, ['number' => $invoiceNumber]);
                return $invoiceId;
            }

            // Payments
            foreach ($calculation['payments'] as $payment) {
                $this->paymentModel->create([
                    'invoice_id'  => $invoiceId,
                    'customer_id' => $customerId,
                    'amount'      => $payment['amount'],
                    'method'      => $payment['method'],
                    'reference'   => $payment['reference'] ?: null,
                    'received_by' => auth_id(),
                    'paid_at'     => date('Y-m-d H:i:s'),
                ]);
            }

            // Package deduction (wallet only — no negative balances allowed)
            foreach ($calculation['package_deductions'] as $deduct) {
                $row = $this->customerPackages->find((int)$deduct['package_id']);
                if (!$row) {
                    continue;
                }
                $newRemaining = round((float)$row['remaining_credits'] - (float)$deduct['credits'], 2);
                $status = $row['status'];
                if ($status === 'active' && $newRemaining <= 0.001) {
                    $status = 'exhausted';
                }
                $this->packageModel->update((int)$row['id'], [
                    'remaining_credits' => $newRemaining,
                    'status'            => $status,
                ]);
                $this->packageTransactionModel->create([
                    'customer_package_id' => (int)$row['id'],
                    'customer_id'         => $customerId,
                    'reference_id'        => $invoiceId,
                    'type'                => 'debit',
                    'credits'             => (float)$deduct['credits'],
                    'amount'              => (float)$deduct['amount'],
                    'description'         => 'Applied to ' . $invoiceNumber,
                ]);
            }

            // Customer financials + ledger
            $this->applyLedger($customerId, $invoiceId, $invoiceNumber, $calculation);

            $this->activity->log('invoice.finalized', 'invoice', $invoiceId, [
                'number'     => $invoiceNumber,
                'payable'    => $calculation['amount_payable'],
                'paid'       => $calculation['amount_paid'],
                'payment_status' => $calculation['payment_status'],
            ]);

            return $invoiceId;
        });
    }

    private function applyLedger(int $customerId, int $invoiceId, string $invoiceNumber, array $calc): void
    {
        // Running balance from the last ledger entry for this customer
        $stmt = $this->ledgerModel->query()->prepare(
            'SELECT balance FROM ledger_entries WHERE customer_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$customerId]);
        $lastBalance = $stmt->fetchColumn();
        $running = round((float)($lastBalance === false ? 0 : $lastBalance), 2);

        // 1) Debit: bill raised (full total)
        $running = round($running + $calc['total'], 2);
        $this->ledgerModel->create([
            'customer_id'  => $customerId,
            'reference_id' => $invoiceId,
            'type'         => 'bill',
            'amount'       => $calc['total'],
            'balance'      => $running,
            'description'  => 'Transaction ' . $invoiceNumber,
        ]);

        // 2) Credit: wallet usage + any recorded payments
        $credits = round((float) $calc['package_deduction'], 2);
        foreach ($calc['payments'] as $payment) {
            $credits += round((float) $payment['amount'], 2);
        }
        if ($credits > 0) {
            $running = round($running - $credits, 2);
            $this->ledgerModel->create([
                'customer_id'  => $customerId,
                'reference_id' => $invoiceId,
                'type'         => 'payment',
                'amount'       => $credits,
                'balance'      => $running,
                'description'  => 'Paid from wallet',
            ]);
        }

        $this->customerModel->update($customerId, [
            'last_visit_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Void / cancel an invoice and fully reverse its financial impact.
     */
    public function void(int $invoiceId): void
    {
        \App\Core\App::getInstance()->db->transaction(function () use ($invoiceId) {
            $invoice = $this->invoices->find($invoiceId);
            if (!$invoice || $invoice['status'] === 'cancelled') {
                throw new RuntimeException('Invoice not found or already cancelled.');
            }

            $number = $invoice['invoice_number'];
            $db = \App\Core\App::getInstance()->db;

            // Restore package credits
            $transactions = $db->fetchAll(
                'SELECT * FROM customer_package_transactions WHERE reference_id = ? AND type = ?',
                [$invoiceId, 'debit']
            );
            foreach ($transactions as $txn) {
                $pkg = $this->customerPackages->find((int)$txn['customer_package_id']);
                if ($pkg) {
                    $restored = (float)$pkg['remaining_credits'] + (float)$txn['credits'];
                    $this->packageModel->update((int)$pkg['id'], [
                        'remaining_credits' => $restored,
                        'status'            => $restored > 0 ? 'active' : $pkg['status'],
                    ]);
                    $this->packageTransactionModel->create([
                        'customer_package_id' => (int)$pkg['id'],
                        'customer_id'         => (int)$invoice['customer_id'],
                        'reference_id'        => null,
                        'type'                => 'credit',
                        'credits'             => (float)$txn['credits'],
                        'amount'              => 0.00,
                        'description'         => "Refund from cancelled invoice {$number}",
                    ]);
                }
            }

            // Remove ledger entries for this invoice
            $db->query('DELETE FROM ledger_entries WHERE reference_id = ?', [$invoiceId]);

            // Remove payments (no FK cascade on payments)
            $db->query('DELETE FROM payments WHERE invoice_id = ?', [$invoiceId]);

            // Mark invoice cancelled (items + allocations cascade via FK)
            $this->invoiceModel->update($invoiceId, [
                'status'  => 'cancelled',
                'paid'    => 0.00,
                'balance' => 0.00,
            ]);

            $this->activity->log('invoice.voided', 'invoice', $invoiceId, ['number' => $number]);
        });
    }
}
