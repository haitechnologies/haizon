<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Model\CreditNote;
use App\Model\CreditNoteItem;
use App\Repository\CreditNoteRepository;
use App\Repository\CustomerRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Helper\DateHelper;

class CreditNoteService
{
    private CreditNoteRepository $creditNoteRepo;
    private CustomerRepository $customerRepo;
    private Database $db;

    public function __construct(CreditNoteRepository $creditNoteRepo, CustomerRepository $customerRepo, Database $db)
    {
        $this->creditNoteRepo = $creditNoteRepo;
        $this->customerRepo = $customerRepo;
        $this->db = $db;
    }

    public function getCreditNote(int $id, int $orgId): CreditNote
    {
        $creditNote = $this->creditNoteRepo->find($id, $orgId);
        if ($creditNote === null) {
            throw new NotFoundException("Credit Note with ID {$id} not found.");
        }
        return $creditNote;
    }

    public function getCreditNoteItems(int $creditNoteId, int $orgId): array
    {
        return $this->creditNoteRepo->findItems($creditNoteId, $orgId);
    }

    public function createNote(array $data, array $itemsData, int $orgId, int $userId): CreditNote
    {
        $this->validateNoteData($data, $orgId);

        if (empty($itemsData)) {
            throw new ValidationException(['items' => "No items added. Please add at least one item."]);
        }

        $this->db->beginTransaction();
        try {
            $prefix = 'FL-CN' . date('ym');
            $lastNoteNo = $this->creditNoteRepo->getLastNoteNoForMonth($prefix, $orgId);
            if ($lastNoteNo !== null) {
                $lastSerial = (int) substr($lastNoteNo, -4);
                $newSerial = $lastSerial + 1;
            } else {
                $newSerial = 1;
            }
            $creditNoteNo = $prefix . '-' . str_pad((string)$newSerial, 4, '0', STR_PAD_LEFT);

            $creditNoteDate = $this->parseDate((string)($data['credit_note_date'] ?? ''));

            $creditNote = new CreditNote(
                id: null,
                organizationId: $orgId,
                creditNoteNo: $creditNoteNo,
                creditNoteDate: $creditNoteDate,
                creditNoteStatus: !empty($data['credit_note_status']) ? trim((string)$data['credit_note_status']) : 'draft',
                referenceNo: !empty($data['reference_no']) ? trim((string)$data['reference_no']) : null,
                customerId: (int)($data['customer_id'] ?? 0),
                invoiceId: (int)($data['invoice_id'] ?? 0),
                warehouseId: (int)($data['warehouse_id'] ?? 0),
                salesPerson: (int)($data['sales_person'] ?? 0),
                customerNotes: !empty($data['customer_notes']) ? trim((string)$data['customer_notes']) : null,
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

            $savedCreditNote = $this->creditNoteRepo->save($creditNote);
            $creditNoteId = $savedCreditNote->id;

            if ($creditNoteId === null) {
                throw new \RuntimeException("Failed to insert credit note header.");
            }

            foreach ($itemsData as $itemData) {
                if (empty($itemData['service'])) {
                    continue;
                }
                $item = new CreditNoteItem(
                    id: null,
                    organizationId: $orgId,
                    creditNoteId: $creditNoteId,
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
                $this->creditNoteRepo->saveItem($item);
            }

            $this->db->commit();

            return $savedCreditNote;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateNote(int $id, array $data, array $itemsData, int $orgId, int $userId): CreditNote
    {
        $creditNote = $this->getCreditNote($id, $orgId);
        $this->validateNoteData($data, $orgId);

        $this->db->beginTransaction();
        try {
            $creditNoteDate = isset($data['credit_note_date']) ? $this->parseDate((string)$data['credit_note_date']) : $creditNote->creditNoteDate;

            $updatedCreditNote = new CreditNote(
                id: $creditNote->id,
                organizationId: $creditNote->organizationId,
                creditNoteNo: isset($data['credit_note_no']) ? trim((string)$data['credit_note_no']) : $creditNote->creditNoteNo,
                creditNoteDate: $creditNoteDate,
                creditNoteStatus: isset($data['credit_note_status']) ? trim((string)$data['credit_note_status']) : $creditNote->creditNoteStatus,
                referenceNo: isset($data['reference_no']) ? (!empty($data['reference_no']) ? trim((string)$data['reference_no']) : null) : $creditNote->referenceNo,
                customerId: isset($data['customer_id']) ? (int)$data['customer_id'] : $creditNote->customerId,
                invoiceId: isset($data['invoice_id']) ? (int)$data['invoice_id'] : $creditNote->invoiceId,
                warehouseId: isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : $creditNote->warehouseId,
                salesPerson: isset($data['sales_person']) ? (int)$data['sales_person'] : $creditNote->salesPerson,
                customerNotes: isset($data['customer_notes']) ? (!empty($data['customer_notes']) ? trim((string)$data['customer_notes']) : null) : $creditNote->customerNotes,
                termsAndConditions: isset($data['terms_and_conditions']) ? (!empty($data['terms_and_conditions']) ? trim((string)$data['terms_and_conditions']) : null) : $creditNote->termsAndConditions,
                grandSubtotal: isset($data['grand_subtotal']) ? (float)$data['grand_subtotal'] : $creditNote->grandSubtotal,
                grandDiscountType: isset($data['grand_discount_type']) ? trim((string)$data['grand_discount_type']) : $creditNote->grandDiscountType,
                grandDiscountTypeValue: isset($data['grand_discount_type_value']) ? (float)$data['grand_discount_type_value'] : $creditNote->grandDiscountTypeValue,
                grandDiscountAmount: isset($data['grand_discount_amount']) ? (float)$data['grand_discount_amount'] : $creditNote->grandDiscountAmount,
                grandAfterDiscount: isset($data['grand_after_discount']) ? (float)$data['grand_after_discount'] : $creditNote->grandAfterDiscount,
                grandTax: isset($data['grand_tax']) ? (float)$data['grand_tax'] : $creditNote->grandTax,
                grandTotal: isset($data['grand_total']) ? (float)$data['grand_total'] : $creditNote->grandTotal,
                publish: isset($data['publish']) ? (bool)$data['publish'] : $creditNote->publish,
                isActive: isset($data['is_active']) ? (bool)$data['is_active'] : $creditNote->isActive,
                createdAt: $creditNote->createdAt,
                createdBy: $creditNote->createdBy,
                updatedBy: $userId,
            );

            $savedCreditNote = $this->creditNoteRepo->save($updatedCreditNote);

            $existingItems = $this->creditNoteRepo->findItems($id, $orgId);
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

                $item = new CreditNoteItem(
                    id: $itemId,
                    organizationId: $orgId,
                    creditNoteId: $id,
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
                $this->creditNoteRepo->saveItem($item);
            }

            $deletedIds = array_diff($existingIds, $incomingIds);
            if (!empty($deletedIds)) {
                $this->creditNoteRepo->deleteItemsByIds($deletedIds, $id, $orgId);
            }

            $this->db->commit();

            return $savedCreditNote;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteNote(int $id, int $orgId): bool
    {
        $this->getCreditNote($id, $orgId);

        $this->db->beginTransaction();
        try {
            $result = $this->creditNoteRepo->delete($id, $orgId);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateNoteData(array $data, int $orgId): void
    {
        if (empty($data['customer_id']) || $data['customer_id'] === 'Please select') {
            throw new ValidationException(['customer_id' => "Please select Customer."]);
        }
        if (empty($data['credit_note_date'])) {
            throw new ValidationException(['credit_note_date' => "Please select Credit Note Date."]);
        }

        $customerId = (int)$data['customer_id'];
        $customer = $this->customerRepo->find($customerId, $orgId);
        if ($customer === null) {
            throw new ValidationException(['customer_id' => "Selected customer does not exist in your organization."]);
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
