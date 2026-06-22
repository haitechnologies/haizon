<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Model\DebitNote;
use App\Model\DebitNoteItem;
use App\Repository\DebitNoteRepository;
use App\Repository\VendorRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Helper\DateHelper;

class DebitNoteService
{
    private DebitNoteRepository $debitNoteRepo;
    private VendorRepository $vendorRepo;
    private Database $db;

    public function __construct(DebitNoteRepository $debitNoteRepo, VendorRepository $vendorRepo, Database $db)
    {
        $this->debitNoteRepo = $debitNoteRepo;
        $this->vendorRepo = $vendorRepo;
        $this->db = $db;
    }

    public function getDebitNote(int $id, int $orgId): DebitNote
    {
        $debitNote = $this->debitNoteRepo->find($id, $orgId);
        if ($debitNote === null) {
            throw new NotFoundException("Debit Note with ID {$id} not found.");
        }
        return $debitNote;
    }

    public function getDebitNoteItems(int $debitNoteId, int $orgId): array
    {
        return $this->debitNoteRepo->findItems($debitNoteId, $orgId);
    }

    public function createNote(array $data, array $itemsData, int $orgId, int $userId): DebitNote
    {
        $this->validateNoteData($data, $orgId);

        if (empty($itemsData)) {
            throw new ValidationException(['items' => "No items added. Please add at least one item."]);
        }

        $this->db->beginTransaction();
        try {
            $prefix = 'FL-DN' . date('ym');
            $lastNoteNo = $this->debitNoteRepo->getLastNoteNoForMonth($prefix, $orgId);
            if ($lastNoteNo !== null) {
                $lastSerial = (int) substr($lastNoteNo, -4);
                $newSerial = $lastSerial + 1;
            } else {
                $newSerial = 1;
            }
            $debitNoteNo = $prefix . '-' . str_pad((string)$newSerial, 4, '0', STR_PAD_LEFT);

            $debitNoteDate = $this->parseDate((string)($data['debit_note_date'] ?? ''));

            $debitNote = new DebitNote(
                id: null,
                organizationId: $orgId,
                debitNoteNo: $debitNoteNo,
                debitNoteDate: $debitNoteDate,
                debitNoteStatus: !empty($data['debit_note_status']) ? trim((string)$data['debit_note_status']) : 'draft',
                referenceNo: !empty($data['reference_no']) ? trim((string)$data['reference_no']) : null,
                vendorId: (int)($data['vendor_id'] ?? 0),
                purchaseId: (int)($data['purchase_id'] ?? 0),
                warehouseId: (int)($data['warehouse_id'] ?? 0),
                purchasePerson: (int)($data['purchase_person'] ?? 0),
                vendorNotes: !empty($data['vendor_notes']) ? trim((string)$data['vendor_notes']) : null,
                termsAndConditions: !empty($data['terms_and_conditions']) ? trim((string)$data['terms_and_conditions']) : null,
                grandSubtotal: (float)($data['grand_subtotal'] ?? 0.0),
                grandDiscountType: !empty($data['grand_discount_type']) ? trim((string)$data['grand_discount_type']) : '0.00',
                grandDiscountTypeValue: (float)($data['grand_discount_type_value'] ?? 0.0),
                grandDiscountAmount: (float)($data['grand_discount_amount'] ?? 0.0),
                grandAfterDiscount: (float)($data['grand_after_discount'] ?? 0.0),
                grandTax: (float)($data['grand_tax'] ?? 0.0),
                grandTotal: (float)($data['grand_total'] ?? 0.0),
                publish: isset($data['publish']) ? (bool)$data['publish'] : true,
                isActive: isset($data['is_active']) ? (bool)$data['is_active'] : true,
                createdBy: $userId,
            );

            $savedDebitNote = $this->debitNoteRepo->save($debitNote);
            $debitNoteId = $savedDebitNote->id;

            if ($debitNoteId === null) {
                throw new \RuntimeException("Failed to insert debit note header.");
            }

            foreach ($itemsData as $itemData) {
                if (empty($itemData['service'])) {
                    continue;
                }
                $item = new DebitNoteItem(
                    id: null,
                    organizationId: $orgId,
                    debitNoteId: $debitNoteId,
                    service: (int)$itemData['service'],
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    qty: isset($itemData['qty']) ? (float)$itemData['qty'] : 1.0,
                    rate: isset($itemData['rate']) ? (float)$itemData['rate'] : 0.0,
                    subTotal: isset($itemData['sub_total']) ? (float)$itemData['sub_total'] : 0.0,
                    tax: isset($itemData['tax']) ? (float)$itemData['tax'] : 0.0,
                    taxAmount: isset($itemData['tax_amount']) ? (float)$itemData['tax_amount'] : 0.0,
                    total: isset($itemData['total']) ? (float)$itemData['total'] : 0.0,
                    createdBy: $userId,
                );
                $this->debitNoteRepo->saveItem($item);
            }

            $this->db->commit();

            return $savedDebitNote;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateNote(int $id, array $data, array $itemsData, int $orgId, int $userId): DebitNote
    {
        $debitNote = $this->getDebitNote($id, $orgId);
        $this->validateNoteData($data, $orgId);

        $this->db->beginTransaction();
        try {
            $debitNoteDate = isset($data['debit_note_date']) ? $this->parseDate((string)$data['debit_note_date']) : $debitNote->debitNoteDate;

            $updatedDebitNote = new DebitNote(
                id: $debitNote->id,
                organizationId: $debitNote->organizationId,
                debitNoteNo: isset($data['debit_note_no']) ? trim((string)$data['debit_note_no']) : $debitNote->debitNoteNo,
                debitNoteDate: $debitNoteDate,
                debitNoteStatus: isset($data['debit_note_status']) ? trim((string)$data['debit_note_status']) : $debitNote->debitNoteStatus,
                referenceNo: isset($data['reference_no']) ? (!empty($data['reference_no']) ? trim((string)$data['reference_no']) : null) : $debitNote->referenceNo,
                vendorId: isset($data['vendor_id']) ? (int)$data['vendor_id'] : $debitNote->vendorId,
                purchaseId: isset($data['purchase_id']) ? (int)$data['purchase_id'] : $debitNote->purchaseId,
                warehouseId: isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : $debitNote->warehouseId,
                purchasePerson: isset($data['purchase_person']) ? (int)$data['purchase_person'] : $debitNote->purchasePerson,
                vendorNotes: isset($data['vendor_notes']) ? (!empty($data['vendor_notes']) ? trim((string)$data['vendor_notes']) : null) : $debitNote->vendorNotes,
                termsAndConditions: isset($data['terms_and_conditions']) ? (!empty($data['terms_and_conditions']) ? trim((string)$data['terms_and_conditions']) : null) : $debitNote->termsAndConditions,
                grandSubtotal: isset($data['grand_subtotal']) ? (float)$data['grand_subtotal'] : $debitNote->grandSubtotal,
                grandDiscountType: isset($data['grand_discount_type']) ? trim((string)$data['grand_discount_type']) : $debitNote->grandDiscountType,
                grandDiscountTypeValue: isset($data['grand_discount_type_value']) ? (float)$data['grand_discount_type_value'] : $debitNote->grandDiscountTypeValue,
                grandDiscountAmount: isset($data['grand_discount_amount']) ? (float)$data['grand_discount_amount'] : $debitNote->grandDiscountAmount,
                grandAfterDiscount: isset($data['grand_after_discount']) ? (float)$data['grand_after_discount'] : $debitNote->grandAfterDiscount,
                grandTax: isset($data['grand_tax']) ? (float)$data['grand_tax'] : $debitNote->grandTax,
                grandTotal: isset($data['grand_total']) ? (float)$data['grand_total'] : $debitNote->grandTotal,
                publish: isset($data['publish']) ? (bool)$data['publish'] : $debitNote->publish,
                isActive: isset($data['is_active']) ? (bool)$data['is_active'] : $debitNote->isActive,
                createdAt: $debitNote->createdAt,
                createdBy: $debitNote->createdBy,
                updatedBy: $userId,
            );

            $savedDebitNote = $this->debitNoteRepo->save($updatedDebitNote);

            $existingItems = $this->debitNoteRepo->findItems($id, $orgId);
            $existingIds = array_map(fn($item) => $item->id, $existingItems);
            $incomingIds = [];

            foreach ($itemsData as $itemData) {
                if (empty($itemData['service'])) {
                    continue;
                }
                $itemId = !empty($itemData['id']) ? (int)$itemData['id'] : null;
                if ($itemId !== null) {
                    $incomingIds[] = $itemId;
                }

                $item = new DebitNoteItem(
                    id: $itemId,
                    organizationId: $orgId,
                    debitNoteId: $id,
                    service: (int)$itemData['service'],
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    qty: isset($itemData['qty']) ? (float)$itemData['qty'] : 1.0,
                    rate: isset($itemData['rate']) ? (float)$itemData['rate'] : 0.0,
                    subTotal: isset($itemData['sub_total']) ? (float)$itemData['sub_total'] : 0.0,
                    tax: isset($itemData['tax']) ? (float)$itemData['tax'] : 0.0,
                    taxAmount: isset($itemData['tax_amount']) ? (float)$itemData['tax_amount'] : 0.0,
                    total: isset($itemData['total']) ? (float)$itemData['total'] : 0.0,
                    createdBy: $userId,
                );
                $this->debitNoteRepo->saveItem($item);
            }

            $deletedIds = array_diff($existingIds, $incomingIds);
            if (!empty($deletedIds)) {
                $this->debitNoteRepo->deleteItemsByIds($deletedIds, $id, $orgId);
            }

            $this->db->commit();

            return $savedDebitNote;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteNote(int $id, int $orgId): bool
    {
        $this->getDebitNote($id, $orgId);

        $this->db->beginTransaction();
        try {
            $result = $this->debitNoteRepo->delete($id, $orgId);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateNoteData(array $data, int $orgId): void
    {
        if (empty($data['vendor_id']) || $data['vendor_id'] === 'Please select') {
            throw new ValidationException(['vendor_id' => "Please select Vendor."]);
        }
        if (empty($data['debit_note_date'])) {
            throw new ValidationException(['debit_note_date' => "Please select Debit Note Date."]);
        }

        $vendorId = (int)$data['vendor_id'];
        $vendor = $this->vendorRepo->find($vendorId, $orgId);
        if ($vendor === null) {
            throw new ValidationException(['vendor_id' => "Selected vendor does not exist in your organization."]);
        }
    }

    private function parseDate(string $date): string
    {
        if (empty($date)) {
            return date('Y-m-d');
        }
        if (strpos($date, '-') !== false) {
            $parts = explode('-', $date);
            if (count($parts) === 3 && (int)$parts[0] > 31) {
                return $date;
            }
        }
        return DateHelper::toDbDate($date) ?: $date;
    }
}
