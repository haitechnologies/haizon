<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\DB;
use App\Model\Purchase;
use App\Model\PurchaseItem;
use App\Repository\PurchaseRepository;
use App\Repository\VendorRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Helper\DateHelper;

class PurchaseService
{
    private PurchaseRepository $purchaseRepo;
    private VendorRepository $vendorRepo;
    private Database $db;

    public function __construct(PurchaseRepository $purchaseRepo, VendorRepository $vendorRepo, Database $db)
    {
        $this->purchaseRepo = $purchaseRepo;
        $this->vendorRepo = $vendorRepo;
        $this->db = $db;
    }

    public function getPurchase(int $id, int $orgId): Purchase
    {
        $purchase = $this->purchaseRepo->find($id, $orgId);
        if ($purchase === null) {
            throw new NotFoundException("Purchase with ID {$id} not found.");
        }
        return $purchase;
    }

    public function getPurchaseItems(int $purchaseId, int $orgId): array
    {
        return $this->purchaseRepo->findItemsByPurchase($purchaseId, $orgId);
    }

    public function createPurchase(array $data, array $itemsData, int $orgId, int $userId): Purchase
    {
        $this->validatePurchaseData($data);

        if (empty($itemsData)) {
            throw new ValidationException(['items' => "No items added. Please add at least one item."]);
        }

        $this->db->beginTransaction();
        try {
            $purchaseDate = $this->parseDate((string)($data['purchase_date'] ?? ''));

            $purchase = new Purchase(
                id: null,
                organizationId: $orgId,
                purchaseDate: $purchaseDate,
                vendorId: (int)($data['vendor_id'] ?? 0),
                purchaseStatus: !empty($data['purchase_status']) ? trim((string)$data['purchase_status']) : 'draft',
                referenceNo: !empty($data['reference_no']) ? trim((string)$data['reference_no']) : null,
                subject: !empty($data['subject']) ? trim((string)$data['subject']) : null,
                warehouseId: (int)($data['warehouse_id'] ?? 0),
                vendorNotes: !empty($data['vendor_notes']) ? trim((string)$data['vendor_notes']) : null,
                termsAndConditions: !empty($data['terms_and_conditions']) ? trim((string)$data['terms_and_conditions']) : null,
                grandSubtotal: (float)($data['grand_subtotal'] ?? 0.0),
                grandDiscountType: !empty($data['grand_discount_type']) ? trim((string)$data['grand_discount_type']) : '0.00',
                grandDiscountTypeValue: (float)($data['grand_discount_type_value'] ?? 0.0),
                grandDiscountAmount: (float)($data['grand_discount_amount'] ?? 0.0),
                grandAfterDiscount: (float)($data['grand_after_discount'] ?? 0.0),
                grandTax: (float)($data['grand_tax'] ?? 0.0),
                grandTotal: (float)($data['grand_total'] ?? 0.0),
                createdBy: $userId,
            );

            $savedPurchase = $this->purchaseRepo->save($purchase);
            $purchaseId = $savedPurchase->id;

            if ($purchaseId === null) {
                throw new \RuntimeException("Failed to insert purchase header.");
            }

            foreach ($itemsData as $itemData) {
                if (empty($itemData['service']) || (int)$itemData['service'] <= 0) {
                    continue;
                }
                $item = new PurchaseItem(
                    id: null,
                    organizationId: $orgId,
                    purchaseId: $purchaseId,
                    service: (int)$itemData['service'],
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    qty: (float)($itemData['qty'] ?? 1.0),
                    rate: (float)($itemData['rate'] ?? 0.0),
                    subTotal: (float)($itemData['sub_total'] ?? 0.0),
                    tax: (float)($itemData['tax'] ?? 0.0),
                    taxAmount: (float)($itemData['tax_amount'] ?? 0.0),
                    total: (float)($itemData['total'] ?? 0.0),
                    createdBy: $userId,
                );
                $this->purchaseRepo->saveItem($item);
            }

            $this->db->commit();

            return $savedPurchase;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updatePurchase(int $id, array $data, array $itemsData, int $orgId, int $userId): Purchase
    {
        $purchase = $this->getPurchase($id, $orgId);
        $this->validatePurchaseData($data);

        $this->db->beginTransaction();
        try {
            $purchaseDate = isset($data['purchase_date']) ? $this->parseDate((string)$data['purchase_date']) : $purchase->purchaseDate;

            $updatedPurchase = new Purchase(
                id: $purchase->id,
                organizationId: $purchase->organizationId,
                purchaseDate: $purchaseDate,
                vendorId: isset($data['vendor_id']) ? (int)$data['vendor_id'] : $purchase->vendorId,
                purchaseStatus: isset($data['purchase_status']) ? (!empty($data['purchase_status']) ? trim((string)$data['purchase_status']) : 'draft') : $purchase->purchaseStatus,
                referenceNo: isset($data['reference_no']) ? (!empty($data['reference_no']) ? trim((string)$data['reference_no']) : null) : $purchase->referenceNo,
                subject: isset($data['subject']) ? (!empty($data['subject']) ? trim((string)$data['subject']) : null) : $purchase->subject,
                warehouseId: isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : $purchase->warehouseId,
                vendorNotes: isset($data['vendor_notes']) ? (!empty($data['vendor_notes']) ? trim((string)$data['vendor_notes']) : null) : $purchase->vendorNotes,
                termsAndConditions: isset($data['terms_and_conditions']) ? (!empty($data['terms_and_conditions']) ? trim((string)$data['terms_and_conditions']) : null) : $purchase->termsAndConditions,
                grandSubtotal: isset($data['grand_subtotal']) ? (float)$data['grand_subtotal'] : $purchase->grandSubtotal,
                grandDiscountType: isset($data['grand_discount_type']) ? (!empty($data['grand_discount_type']) ? trim((string)$data['grand_discount_type']) : '0.00') : $purchase->grandDiscountType,
                grandDiscountTypeValue: isset($data['grand_discount_type_value']) ? (float)$data['grand_discount_type_value'] : $purchase->grandDiscountTypeValue,
                grandDiscountAmount: isset($data['grand_discount_amount']) ? (float)$data['grand_discount_amount'] : $purchase->grandDiscountAmount,
                grandAfterDiscount: isset($data['grand_after_discount']) ? (float)$data['grand_after_discount'] : $purchase->grandAfterDiscount,
                grandTax: isset($data['grand_tax']) ? (float)$data['grand_tax'] : $purchase->grandTax,
                grandTotal: isset($data['grand_total']) ? (float)$data['grand_total'] : $purchase->grandTotal,
                createdAt: $purchase->createdAt,
                createdBy: $purchase->createdBy,
                updatedBy: $userId,
            );

            $savedPurchase = $this->purchaseRepo->save($updatedPurchase);

            $existingItems = $this->purchaseRepo->findItemsByPurchase($id, $orgId);
            $existingIds = array_map(fn($item) => $item->id, $existingItems);
            $incomingIds = [];

            foreach ($itemsData as $itemData) {
                $itemService = isset($itemData['service']) ? (int)$itemData['service'] : 0;

                $itemId = !empty($itemData['id']) ? (int)$itemData['id'] : null;

                if ($itemId === null && $itemService <= 0) {
                    continue;
                }

                if ($itemId !== null) {
                    $incomingIds[] = $itemId;
                }

                $item = new PurchaseItem(
                    id: $itemId,
                    organizationId: $orgId,
                    purchaseId: $id,
                    service: $itemService,
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    qty: (float)($itemData['qty'] ?? 1.0),
                    rate: (float)($itemData['rate'] ?? 0.0),
                    subTotal: (float)($itemData['sub_total'] ?? 0.0),
                    tax: (float)($itemData['tax'] ?? 0.0),
                    taxAmount: (float)($itemData['tax_amount'] ?? 0.0),
                    total: (float)($itemData['total'] ?? 0.0),
                    createdBy: $userId,
                );
                $this->purchaseRepo->saveItem($item);
            }

            $deletedIds = array_diff($existingIds, $incomingIds);
            if (!empty($deletedIds)) {
                $this->purchaseRepo->deleteItemsByIds($deletedIds, $id, $orgId);
            }

            $this->db->commit();

            return $savedPurchase;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deletePurchase(int $id, int $orgId): bool
    {
        $this->getPurchase($id, $orgId);

        $this->db->beginTransaction();
        try {
            $result = $this->purchaseRepo->delete($id, $orgId);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validatePurchaseData(array $data): void
    {
        if (empty($data['vendor_id']) || (int)$data['vendor_id'] <= 0) {
            throw new ValidationException(['vendor_id' => "Please select Vendor."]);
        }
        // Verify vendor exists
        $vendor = $this->vendorRepo->find((int)$data['vendor_id']);
        if ($vendor === null) {
            throw new ValidationException(['vendor_id' => "Selected vendor does not exist."]);
        }
        if (empty($data['purchase_date'])) {
            throw new ValidationException(['purchase_date' => "Please select Purchase Date."]);
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
