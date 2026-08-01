<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerNote;
use App\Repositories\CustomerRepository;

final class CustomerService
{
    public function __construct(
        private CustomerRepository $customers,
        private ActivityService $activity,
        private Customer $customerModel,
        private CustomerNote $noteModel
    ) {
    }

    public function create(array $data): int
    {
        $data['status'] = $data['status'] ?? 'active';
        $id = $this->customerModel->create($data);
        $this->activity->log('customer.created', 'customer', $id, ['name' => $data['name']]);
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->customerModel->update($id, $data);
        $this->activity->log('customer.updated', 'customer', $id, ['name' => $data['name'] ?? null]);
    }

    public function delete(int $id): void
    {
        $this->customerModel->delete($id);
        $this->activity->log('customer.deleted', 'customer', $id);
    }

    public function addNote(int $customerId, string $note): void
    {
        $this->noteModel->create([
            'customer_id' => $customerId,
            'created_by'  => auth_id(),
            'note'        => $note,
        ]);
        $this->activity->log('customer.note_added', 'customer', $customerId);
    }

    public function notes(int $customerId): array
    {
        $stmt = $this->noteModel->query()->prepare(
            "SELECT n.*, u.name AS user_name
             FROM customer_notes n LEFT JOIN users u ON u.id = n.created_by
             WHERE n.customer_id = ? ORDER BY n.created_at DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
}
