<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerPackage;
use App\Models\CustomerPackageTransaction;
use App\Repositories\CustomerPackageRepository;
use App\Repositories\PackageRepository;

final class CustomerPackageService
{
    public function __construct(
        private CustomerPackageRepository $customerPackages,
        private PackageRepository $packages,
        private CustomerPackage $packageModel,
        private CustomerPackageTransaction $transactionModel,
        private ActivityService $activity
    ) {
    }

    /**
     * Assign a package to a customer.
     *
     * @param int  $customerId
     * @param int|null $packageId    predefined template id (null for custom)
     * @param array $custom          custom package data: name, price, credits, validity_days, notes
     */
    public function assign(int $customerId, ?int $packageId, array $custom = []): int
    {
        $name = '';
        $price = 0.0;
        $credits = 0;
        $validityDays = null;

        if ($packageId) {
            $template = $this->packages->find((int)$packageId);
            if (!$template) {
                throw new \InvalidArgumentException('Selected package template not found.');
            }
            $name = $template['name'];
            $price = (float)$template['selling_price'];
            $credits = (int)$template['credits'];
            $validityDays = $template['validity_days'] ? (int)$template['validity_days'] : null;
        } else {
            $name = trim((string)($custom['name'] ?? ''));
            $price = (float)($custom['price'] ?? 0);
            $credits = (int)($custom['credits'] ?? 0);
            $validityDays = ($custom['validity_days'] ?? null) !== '' && $custom['validity_days'] !== null
                ? (int)$custom['validity_days']
                : null;
            if ($name === '') {
                throw new \InvalidArgumentException('A package name is required.');
            }
            if ($price <= 0 || $credits <= 0) {
                throw new \InvalidArgumentException('Price and credits must be greater than zero.');
            }
        }

        $startsOn = date('Y-m-d');
        $expiresOn = $validityDays ? date('Y-m-d', strtotime("+{$validityDays} days")) : null;
        $valuePerCredit = $credits > 0 ? round($price / $credits, 2) : 0.00;

        $id = $this->packageModel->create([
            'customer_id'        => $customerId,
            'package_id'         => $packageId,
            'name'               => $name,
            'selling_price'      => $price,
            'credits'            => $credits,
            'remaining_credits'  => $credits,
            'value_per_credit'   => $valuePerCredit,
            'validity_days'      => $validityDays,
            'starts_on'          => $startsOn,
            'expires_on'         => $expiresOn,
            'status'             => 'active',
            'notes'              => $custom['notes'] ?? null,
        ]);

        $this->transactionModel->create([
            'customer_package_id' => $id,
            'customer_id'         => $customerId,
            'type'                => 'purchase',
            'credits'             => $credits,
            'amount'              => $price,
            'description'         => 'Package purchase',
        ]);

        $this->activity->log('package.assigned', 'customer_package', $id, [
            'customer_id' => $customerId,
            'name'        => $name,
            'price'       => $price,
        ]);

        return $id;
    }

    public function cancel(int $customerPackageId, string $reason = ''): void
    {
        $row = $this->customerPackages->find($customerPackageId);
        if (!$row || $row['status'] !== 'active') {
            throw new \InvalidArgumentException('Package is not active.');
        }

        $this->packageModel->update($customerPackageId, ['status' => 'cancelled', 'notes' => $reason]);
        $this->activity->log('package.cancelled', 'customer_package', $customerPackageId, ['reason' => $reason]);
    }

    public function expireOverdue(): int
    {
        return $this->customerPackages->expireOverdue();
    }

    public function activeFor(int $customerId): array
    {
        return $this->customerPackages->activeFor($customerId);
    }
}
