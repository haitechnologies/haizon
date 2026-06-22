<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\LeadQuotation;
use App\Model\LeadQuotationItem;
use App\Repository\LeadQuotationRepository;
use App\Core\Database;
use App\Core\DB;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

/**
 * LeadQuotation Service
 *
 * Implements business logic and validations for lead-focused quotations and line items.
 */
class LeadQuotationService
{
    private LeadQuotationRepository $leadQuotationRepo;
    private Database $db;

    public function __construct(LeadQuotationRepository $leadQuotationRepo, Database $db)
    {
        $this->leadQuotationRepo = $leadQuotationRepo;
        $this->db = $db;
    }

    /**
     * Get LeadQuotation by ID and organization
     *
     * @throws NotFoundException
     */
    public function getQuotation(int $id, int $orgId): LeadQuotation
    {
        $quotation = $this->leadQuotationRepo->find($id, $orgId);
        if ($quotation === null) {
            throw new NotFoundException("Lead Quotation with ID {$id} not found.");
        }
        return $quotation;
    }

    /**
     * Get items of a lead quotation
     */
    public function getQuotationItems(int $quotationId, int $orgId): array
    {
        return $this->leadQuotationRepo->findItemsByQuotation($quotationId, $orgId);
    }

    /**
     * Create a new lead quotation
     *
     * @throws ValidationException
     */
    public function createQuotation(array $data, array $itemsData, int $orgId, int $userId): LeadQuotation
    {
        $this->validateQuotationData($data, $orgId);

        if (empty($itemsData)) {
            throw new ValidationException(['items' => "No items added. Please add at least one item."]);
        }

        $this->db->beginTransaction();
        try {
            $prefix = 'FL-LQ' . date('ym');
            $lastQtNo = $this->leadQuotationRepo->getLastQuotationNoForMonth($prefix, $orgId);
            if ($lastQtNo !== null) {
                $lastSerial = (int) substr($lastQtNo, -4);
                $newSerial = $lastSerial + 1;
            } else {
                $newSerial = 1;
            }
            $quotationNo = $prefix . '-' . str_pad((string)$newSerial, 4, '0', STR_PAD_LEFT);

            $quotationDate = (string)($data['quotation_date'] ?? date('Y-m-d'));
            if (strpos($quotationDate, '-') === false) {
                $quotationDate = \App\Helper\DateHelper::toDisplayDate($quotationDate) ?: $quotationDate;
            }
            $expiryDate = (string)($data['expiry_date'] ?? '');
            if (!empty($expiryDate)) {
                if (strpos($expiryDate, '-') === false) {
                    $expiryDate = \App\Helper\DateHelper::toDisplayDate($expiryDate) ?: $expiryDate;
                }
            } else {
                $expiryDate = '1970-01-01';
            }

            $leadId = (int)$data['lead_id'];

            $quotation = new LeadQuotation(
                id: null,
                organizationId: $orgId,
                quotationNo: $quotationNo,
                customerId: 0,
                quotationStatus: !empty($data['quotation_status']) ? trim((string)$data['quotation_status']) : 'draft',
                quotationDate: $quotationDate,
                expiryDate: $expiryDate,
                leadId: $leadId,
                warehouseId: !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : 0,
                expectedShipmentDate: null,
                paymentTerm: 0,
                shipmentType: null,
                salesPerson: 0,
                jobReferenceNo: null,
                masterAwbNo: null,
                shipper: 0,
                consignee: 0,
                origin: 0,
                destination: 0,
                noOfPacks: 0,
                grossWeight: 0.0,
                chargeableWeight: 0.0,
                volume: 0.0,
                termsAndConditions: !empty($data['terms_and_conditions']) ? trim((string)$data['terms_and_conditions']) : null,
                grandSubtotal: (float)($data['grand_subtotal'] ?? 0.0),
                grandDiscountType: !empty($data['grand_discount_type']) ? trim((string)$data['grand_discount_type']) : '0.00',
                grandDiscountTypeValue: !empty($data['grand_discount_type_value']) ? (float)$data['grand_discount_type_value'] : 0.0,
                grandDiscountAmount: !empty($data['grand_discount_amount']) ? (float)$data['grand_discount_amount'] : 0.0,
                grandAfterDiscount: !empty($data['grand_after_discount']) ? (float)$data['grand_after_discount'] : 0.0,
                customerNotes: !empty($data['customer_notes']) ? trim((string)$data['customer_notes']) : null,
                grandTax: !empty($data['grand_tax']) ? (float)$data['grand_tax'] : 0.0,
                grandTotal: (float)($data['grand_total'] ?? 0.0),
                publish: isset($data['publish']) ? (bool)$data['publish'] : true,
                isActive: isset($data['is_active']) ? (bool)$data['is_active'] : true,
                createdBy: $userId,
                pdf: !empty($data['pdf']) ? trim((string)$data['pdf']) : null
            );

            $savedQuotation = $this->leadQuotationRepo->save($quotation);
            $quotationId = $savedQuotation->id;

            if ($quotationId === null) {
                throw new \RuntimeException("Failed to insert lead quotation header.");
            }

            foreach ($itemsData as $itemData) {
                if (empty($itemData['service'])) {
                    continue;
                }
                $item = new LeadQuotationItem(
                    id: null,
                    organizationId: $orgId,
                    quotationId: $quotationId,
                    service: (int)$itemData['service'],
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    qty: isset($itemData['qty']) ? (float)$itemData['qty'] : 1.0,
                    rate: isset($itemData['rate']) ? (float)$itemData['rate'] : 0.0,
                    subTotal: isset($itemData['sub_total']) ? (float)$itemData['sub_total'] : 0.0,
                    tax: isset($itemData['tax']) ? (float)$itemData['tax'] : 0.0,
                    taxAmount: isset($itemData['tax_amount']) ? (float)$itemData['tax_amount'] : 0.0,
                    total: isset($itemData['total']) ? (float)$itemData['total'] : 0.0,
                    discountType: !empty($itemData['discount_type']) ? trim((string)$itemData['discount_type']) : null,
                    discountTypeValue: !empty($itemData['discount_type_value']) ? (float)$itemData['discount_type_value'] : 0.0,
                    discountAmount: !empty($itemData['discount_amount']) ? (float)$itemData['discount_amount'] : 0.0,
                    createdBy: $userId
                );
                $this->leadQuotationRepo->saveItem($item);
            }

            $this->db->commit();

            return $savedQuotation;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing lead quotation
     *
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function updateQuotation(int $id, array $data, array $itemsData, int $orgId, int $userId): LeadQuotation
    {
        $quotation = $this->getQuotation($id, $orgId);
        $this->validateQuotationData($data, $orgId);

        $this->db->beginTransaction();
        try {
            $quotationDate = isset($data['quotation_date']) ? (string)$data['quotation_date'] : $quotation->quotationDate;
            if (strpos($quotationDate, '-') === false) {
                $quotationDate = \App\Helper\DateHelper::toDisplayDate($quotationDate) ?: $quotationDate;
            }
            $expiryDate = isset($data['expiry_date']) ? (string)$data['expiry_date'] : $quotation->expiryDate;
            if (!empty($expiryDate)) {
                if (strpos($expiryDate, '-') === false) {
                    $expiryDate = \App\Helper\DateHelper::toDisplayDate($expiryDate) ?: $expiryDate;
                }
            } else {
                $expiryDate = '1970-01-01';
            }

            $updatedQuotation = new LeadQuotation(
                id: $quotation->id,
                organizationId: $quotation->organizationId,
                quotationNo: isset($data['quotation_no']) ? trim((string)$data['quotation_no']) : $quotation->quotationNo,
                customerId: 0,
                quotationStatus: isset($data['quotation_status']) ? trim((string)$data['quotation_status']) : $quotation->quotationStatus,
                quotationDate: $quotationDate,
                expiryDate: $expiryDate,
                leadId: isset($data['lead_id']) ? (int)$data['lead_id'] : $quotation->leadId,
                warehouseId: isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : $quotation->warehouseId,
                expectedShipmentDate: null,
                paymentTerm: 0,
                shipmentType: null,
                salesPerson: 0,
                jobReferenceNo: null,
                masterAwbNo: null,
                shipper: 0,
                consignee: 0,
                origin: 0,
                destination: 0,
                noOfPacks: 0,
                grossWeight: 0.0,
                chargeableWeight: 0.0,
                volume: 0.0,
                termsAndConditions: isset($data['terms_and_conditions']) ? (!empty($data['terms_and_conditions']) ? trim((string)$data['terms_and_conditions']) : null) : $quotation->termsAndConditions,
                grandSubtotal: isset($data['grand_subtotal']) ? (float)$data['grand_subtotal'] : $quotation->grandSubtotal,
                grandDiscountType: isset($data['grand_discount_type']) ? trim((string)$data['grand_discount_type']) : $quotation->grandDiscountType,
                grandDiscountTypeValue: isset($data['grand_discount_type_value']) ? (float)$data['grand_discount_type_value'] : $quotation->grandDiscountTypeValue,
                grandDiscountAmount: isset($data['grand_discount_amount']) ? (float)$data['grand_discount_amount'] : $quotation->grandDiscountAmount,
                grandAfterDiscount: isset($data['grand_after_discount']) ? (float)$data['grand_after_discount'] : $quotation->grandAfterDiscount,
                customerNotes: isset($data['customer_notes']) ? (!empty($data['customer_notes']) ? trim((string)$data['customer_notes']) : null) : $quotation->customerNotes,
                grandTax: isset($data['grand_tax']) ? (float)$data['grand_tax'] : $quotation->grandTax,
                grandTotal: isset($data['grand_total']) ? (float)$data['grand_total'] : $quotation->grandTotal,
                publish: isset($data['publish']) ? (bool)$data['publish'] : $quotation->publish,
                isActive: isset($data['is_active']) ? (bool)$data['is_active'] : $quotation->publish,
                createdAt: $quotation->createdAt,
                createdBy: $quotation->createdBy,
                updatedBy: $userId,
                pdf: isset($data['pdf']) ? trim((string)$data['pdf']) : $quotation->pdf
            );

            $savedQuotation = $this->leadQuotationRepo->save($updatedQuotation);

            $existingItems = $this->leadQuotationRepo->findItemsByQuotation($id, $orgId);
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

                $item = new LeadQuotationItem(
                    id: $itemId,
                    organizationId: $orgId,
                    quotationId: $id,
                    service: (int)$itemData['service'],
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    qty: isset($itemData['qty']) ? (float)$itemData['qty'] : 1.0,
                    rate: isset($itemData['rate']) ? (float)$itemData['rate'] : 0.0,
                    subTotal: isset($itemData['sub_total']) ? (float)$itemData['sub_total'] : 0.0,
                    tax: isset($itemData['tax']) ? (float)$itemData['tax'] : 0.0,
                    taxAmount: isset($itemData['tax_amount']) ? (float)$itemData['tax_amount'] : 0.0,
                    total: isset($itemData['total']) ? (float)$itemData['total'] : 0.0,
                    discountType: !empty($itemData['discount_type']) ? trim((string)$itemData['discount_type']) : null,
                    discountTypeValue: !empty($itemData['discount_type_value']) ? (float)$itemData['discount_type_value'] : 0.0,
                    discountAmount: !empty($itemData['discount_amount']) ? (float)$itemData['discount_amount'] : 0.0,
                    createdBy: $userId
                );
                $this->leadQuotationRepo->saveItem($item);
            }

            $deletedIds = array_diff($existingIds, $incomingIds);
            if (!empty($deletedIds)) {
                $this->leadQuotationRepo->deleteItemsByIds($deletedIds, $id, $orgId);
            }

            $this->db->commit();

            return $savedQuotation;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * List all lead quotations in an organization
     */
    public function list(int $orgId): array
    {
        return $this->leadQuotationRepo->findAll($orgId);
    }

    /**
     * Delete a lead quotation and its items
     */
    public function deleteQuotation(int $id, int $orgId): bool
    {
        $quotation = $this->getQuotation($id, $orgId);

        $this->db->beginTransaction();
        try {
            $result = $this->leadQuotationRepo->delete($id, $orgId);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update status of a lead quotation
     */
    public function updateStatus(int $id, string $status, int $orgId): bool
    {
        $allowedStatuses = ['draft', 'sent', 'accepted', 'rejected', 'expired', 'confirmed'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new ValidationException(['status' => "Invalid status: {$status}"]);
        }
        return $this->leadQuotationRepo->updateStatus($id, $status, $orgId);
    }

    /**
     * Update lead quotation PDF path
     */
    public function updatePdf(int $id, string $pdfFilename, int $orgId): bool
    {
        return $this->leadQuotationRepo->updatePdf($id, $pdfFilename, $orgId);
    }

    /**
     * Validate LeadQuotation fields
     *
     * @throws ValidationException
     */
    private function validateQuotationData(array $data, int $orgId): void
    {
        if (empty($data['lead_id']) || $data['lead_id'] === 'Please select' || $data['lead_id'] === '0') {
            throw new ValidationException(['lead_id' => "Please select a Lead."]);
        }
        if (empty($data['quotation_date'])) {
            throw new ValidationException(['quotation_date' => "Please select Quotation Date."]);
        }
        if (empty($data['warehouse_id']) || $data['warehouse_id'] === 'Please select' || $data['warehouse_id'] === '0') {
            throw new ValidationException(['warehouse_id' => "Please select a Warehouse."]);
        }

        $leadId = (int)$data['lead_id'];
        $sql = "SELECT id FROM `" . DB::LEADS . "` WHERE id = :id AND is_active = 1";
        $lead = $this->db->fetchOne($sql, ['id' => $leadId]);
        if ($lead === null) {
            throw new ValidationException(['lead_id' => "Selected lead does not exist or is inactive."]);
        }
    }
}
