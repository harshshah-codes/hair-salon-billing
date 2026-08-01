<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;

class InvoiceService extends BaseService
{
    public function __construct(
        private InvoiceRepository $invoices = new InvoiceRepository(),
        private PaymentRepository $payments = new PaymentRepository()
    ) {
        parent::__construct();
    }

    /**
     * Record a payment against an invoice.
     */
    public function recordPayment(int $invoiceId, array $input): array
    {
        $invoice = $this->invoices->find($invoiceId);
        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }
        if (in_array($invoice['status'], ['paid', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'This invoice is already ' . $invoice['status'] . '.'];
        }

        $amount = round((float) ($input['amount'] ?? 0), 2);
        $balance = round((float) $invoice['balance'], 2);
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
        }
        if ($amount > $balance + 0.009) {
            return ['success' => false, 'message' => 'Payment exceeds the outstanding balance of ' . money($balance) . '.'];
        }

        $method = in_array($input['method'] ?? '', ['cash', 'card', 'upi', 'bank', 'other'], true) ? $input['method'] : 'cash';

        try {
            $this->db->transaction(function () use ($invoiceId, $invoice, $amount, $method, $input) {
                $this->db->insert('payments', [
                    'invoice_id' => $invoiceId,
                    'customer_id' => (int) $invoice['customer_id'],
                    'amount' => $amount,
                    'method' => $method,
                    'reference' => trim((string) ($input['reference'] ?? '')) ?: null,
                    'received_by' => auth_id(),
                    'paid_at' => date('Y-m-d H:i:s'),
                ]);

                $newPaid = round((float) $invoice['paid'] + $amount, 2);
                $newBalance = round((float) $invoice['balance'] - $amount, 2);
                $status = $newBalance <= 0.005 ? 'paid' : 'partially_paid';

                $this->invoices->update($invoiceId, [
                    'paid' => $newPaid,
                    'balance' => $newBalance <= 0 ? 0 : $newBalance,
                    'status' => $status,
                ]);

                $current = (float) $this->db->fetchColumn(
                    "SELECT `balance` FROM ledger_entries WHERE customer_id = ? ORDER BY id DESC LIMIT 1",
                    [(int) $invoice['customer_id']]
                );
                $this->db->insert('ledger_entries', [
                    'customer_id' => (int) $invoice['customer_id'],
                    'type' => 'payment',
                    'amount' => $amount,
                    'balance' => round(max(0, $current - $amount), 2),
                    'reference_id' => $invoiceId,
                    'description' => 'Payment received on ' . $invoice['invoice_number'],
                ]);
            });
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Payment could not be recorded. Please try again.'];
        }

        $this->logActivity('billing.payment', money($amount) . " received against {$invoice['invoice_number']}");
        return ['success' => true, 'message' => 'Payment of ' . money($amount) . ' recorded successfully.'];
    }

    /**
     * Cancel an invoice (no ledger/payment reversals needed for new installs).
     */
    public function cancel(int $invoiceId): array
    {
        $invoice = $this->invoices->find($invoiceId);
        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }
        if (in_array($invoice['status'], ['paid', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'This invoice cannot be cancelled.'];
        }

        $this->invoices->update($invoiceId, ['status' => 'cancelled', 'balance' => 0]);
        $this->logActivity('billing.cancel', "Invoice {$invoice['invoice_number']} cancelled");
        return ['success' => true, 'message' => 'Invoice cancelled.'];
    }
}
