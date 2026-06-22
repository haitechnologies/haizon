<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\SaleOrder;
use App\Model\SaleOrderItem;
use App\Repository\SaleOrderRepository;
use App\Repository\CustomerRepository;
use App\Core\Database;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

/**
 * SaleOrder Service
 *
 * Implements business logic and validations for sale orders and line items.
 */
class SaleOrderService
{
    private SaleOrderRepository $saleOrderRepo;
    private CustomerRepository $customerRepo;
    private Database $db;

    public function __construct(SaleOrderRepository $saleOrderRepo, CustomerRepository $customerRepo, Database $db)
    {
        $this->saleOrderRepo = $saleOrderRepo;
        $this->customerRepo = $customerRepo;
        $this->db = $db;
    }

    /**
     * Get SaleOrder by ID and organization
     *
     * @throws NotFoundException
     */
    public function getSaleOrder(int $id, int $orgId): SaleOrder
    {
        $saleOrder = $this->saleOrderRepo->find($id, $orgId);
        if ($saleOrder === null) {
            throw new NotFoundException("Sale Order with ID {$id} not found.");
        }
        return $saleOrder;
    }

    /**
     * Get items of a sale order
     */
    public function getSaleOrderItems(int $saleOrderId, int $orgId): array
    {
        return $this->saleOrderRepo->findItemsBySaleOrder($saleOrderId, $orgId);
    }

    /**
     * Create a new sale order
     *
     * @throws ValidationException
     */
    public function createSaleOrder(array $data, array $itemsData, int $orgId, int $userId): SaleOrder
    {
        $this->validateSaleOrderData($data, $orgId);

        if (empty($itemsData)) {
            throw new ValidationException(['items' => "No items added. Please add at least one item."]);
        }

        $this->db->beginTransaction();
        try {
            // Auto generate SaleOrder number
            $prefix = 'FL-SO' . date('ym');
            $lastSoNo = $this->saleOrderRepo->getLastSaleOrderNoForMonth($prefix, $orgId);
            if ($lastSoNo !== null) {
                $lastSerial = (int) substr($lastSoNo, -4);
                $newSerial = $lastSerial + 1;
            } else {
                $newSerial = 1;
            }
            $saleOrderNo = $prefix . '-' . str_pad((string)$newSerial, 4, '0', STR_PAD_LEFT);

            // Date parsing
            $saleOrderDate = (string)($data['sale_order_date'] ?? date('Y-m-d'));
            if (strpos($saleOrderDate, '-') === false) {
                $saleOrderDate = \App\Helper\DateHelper::toDisplayDate($saleOrderDate) ?: $saleOrderDate;
            }
            $expiryDate = (string)($data['expiry_date'] ?? '');
            if (!empty($expiryDate)) {
                if (strpos($expiryDate, '-') === false) {
                    $expiryDate = \App\Helper\DateHelper::toDisplayDate($expiryDate) ?: $expiryDate;
                }
            } else {
                $expiryDate = '1970-01-01';
            }

            $expectedShipmentDate = (string)($data['expected_shipment_date'] ?? '');
            if (!empty($expectedShipmentDate)) {
                if (strpos($expectedShipmentDate, '-') === false) {
                    $expectedShipmentDate = \App\Helper\DateHelper::toDisplayDate($expectedShipmentDate) ?: $expectedShipmentDate;
                }
            } else {
                $expectedShipmentDate = '1970-01-01';
            }

            $grandSubtotal = (float)($data['grand_subtotal'] ?? 0.0);
            $grandTotal = (float)($data['grand_total'] ?? 0.0);

            $saleOrder = new SaleOrder(
                id: null,
                organizationId: $orgId,
                saleOrderNo: $saleOrderNo,
                customerId: (int)$data['customer_id'],
                saleOrderStatus: !empty($data['sale_order_status']) ? trim((string)$data['sale_order_status']) : 'draft',
                saleOrderDate: $saleOrderDate,
                expiryDate: $expiryDate,
                referenceNo: !empty($data['reference_no']) ? trim((string)$data['reference_no']) : null,
                warehouseId: !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : 0,
                expectedShipmentDate: $expectedShipmentDate,
                paymentTerm: !empty($data['payment_term']) ? (int)$data['payment_term'] : 0,
                shipmentType: !empty($data['shipment_type']) ? trim((string)$data['shipment_type']) : null,
                salesPerson: !empty($data['sales_person']) ? (int)$data['sales_person'] : 0,
                jobReferenceNo: !empty($data['job_reference_no']) ? trim((string)$data['job_reference_no']) : null,
                masterAwbNo: !empty($data['master_awb_no']) ? trim((string)$data['master_awb_no']) : null,
                shipper: !empty($data['shipper']) ? (int)$data['shipper'] : 0,
                consignee: !empty($data['consignee']) ? (int)$data['consignee'] : 0,
                origin: !empty($data['origin']) ? (int)$data['origin'] : 0,
                destination: !empty($data['destination']) ? (int)$data['destination'] : 0,
                noOfPacks: !empty($data['no_of_packs']) ? (int)$data['no_of_packs'] : 0,
                grossWeight: !empty($data['gross_weight']) ? (float)$data['gross_weight'] : 0.0,
                chargeableWeight: !empty($data['chargeable_weight']) ? (float)$data['chargeable_weight'] : 0.0,
                volume: !empty($data['volume']) ? (float)$data['volume'] : 0.0,
                termsAndConditions: !empty($data['terms_and_conditions']) ? trim((string)$data['terms_and_conditions']) : null,
                grandSubtotal: $grandSubtotal,
                grandDiscountType: !empty($data['grand_discount_type']) ? trim((string)$data['grand_discount_type']) : '0.00',
                grandDiscountTypeValue: !empty($data['grand_discount_type_value']) ? (float)$data['grand_discount_type_value'] : 0.0,
                grandDiscountAmount: !empty($data['grand_discount_amount']) ? (float)$data['grand_discount_amount'] : 0.0,
                grandAfterDiscount: !empty($data['grand_after_discount']) ? (float)$data['grand_after_discount'] : 0.0,
                customerNotes: !empty($data['customer_notes']) ? trim((string)$data['customer_notes']) : null,
                grandTax: !empty($data['grand_tax']) ? (float)$data['grand_tax'] : 0.0,
                grandTotal: $grandTotal,
                publish: isset($data['publish']) ? (bool)$data['publish'] : true,
                isActive: isset($data['is_active']) ? (bool)$data['is_active'] : true,
                createdBy: $userId,
                pdf: !empty($data['pdf']) ? trim((string)$data['pdf']) : null
            );

            $savedSaleOrder = $this->saleOrderRepo->save($saleOrder);
            $saleOrderId = $savedSaleOrder->id;

            if ($saleOrderId === null) {
                throw new \RuntimeException("Failed to insert sale order header.");
            }

            // Save line items
            foreach ($itemsData as $itemData) {
                if (empty($itemData['service'])) {
                    continue;
                }
                $item = new SaleOrderItem(
                    id: null,
                    organizationId: $orgId,
                    saleOrderId: $saleOrderId,
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
                $this->saleOrderRepo->saveItem($item);
            }

            $this->db->commit();

            return $savedSaleOrder;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing sale order
     *
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function updateSaleOrder(int $id, array $data, array $itemsData, int $orgId, int $userId): SaleOrder
    {
        $saleOrder = $this->getSaleOrder($id, $orgId);
        $this->validateSaleOrderData($data, $orgId);

        $this->db->beginTransaction();
        try {
            // Date parsing
            $saleOrderDate = isset($data['sale_order_date']) ? (string)$data['sale_order_date'] : $saleOrder->saleOrderDate;
            if (strpos($saleOrderDate, '-') === false) {
                $saleOrderDate = \App\Helper\DateHelper::toDisplayDate($saleOrderDate) ?: $saleOrderDate;
            }
            $expiryDate = isset($data['expiry_date']) ? (string)$data['expiry_date'] : $saleOrder->expiryDate;
            if (!empty($expiryDate)) {
                if (strpos($expiryDate, '-') === false) {
                    $expiryDate = \App\Helper\DateHelper::toDisplayDate($expiryDate) ?: $expiryDate;
                }
            } else {
                $expiryDate = '1970-01-01';
            }

            $expectedShipmentDate = isset($data['expected_shipment_date']) ? (string)$data['expected_shipment_date'] : $saleOrder->expectedShipmentDate;
            if (!empty($expectedShipmentDate)) {
                if (strpos($expectedShipmentDate, '-') === false) {
                    $expectedShipmentDate = \App\Helper\DateHelper::toDisplayDate($expectedShipmentDate) ?: $expectedShipmentDate;
                }
            } else {
                $expectedShipmentDate = '1970-01-01';
            }

            $grandSubtotal = isset($data['grand_subtotal']) ? (float)$data['grand_subtotal'] : $saleOrder->grandSubtotal;
            $grandTotal = isset($data['grand_total']) ? (float)$data['grand_total'] : $saleOrder->grandTotal;

            $updatedSaleOrder = new SaleOrder(
                id: $saleOrder->id,
                organizationId: $saleOrder->organizationId,
                saleOrderNo: isset($data['sale_order_no']) ? trim((string)$data['sale_order_no']) : $saleOrder->saleOrderNo,
                customerId: isset($data['customer_id']) ? (int)$data['customer_id'] : $saleOrder->customerId,
                saleOrderStatus: isset($data['sale_order_status']) ? trim((string)$data['sale_order_status']) : $saleOrder->saleOrderStatus,
                saleOrderDate: $saleOrderDate,
                expiryDate: $expiryDate,
                referenceNo: isset($data['reference_no']) ? (!empty($data['reference_no']) ? trim((string)$data['reference_no']) : null) : $saleOrder->referenceNo,
                warehouseId: isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : $saleOrder->warehouseId,
                expectedShipmentDate: $expectedShipmentDate,
                paymentTerm: isset($data['payment_term']) ? (int)$data['payment_term'] : $saleOrder->paymentTerm,
                shipmentType: isset($data['shipment_type']) ? (!empty($data['shipment_type']) ? trim((string)$data['shipment_type']) : null) : $saleOrder->shipmentType,
                salesPerson: isset($data['sales_person']) ? (int)$data['sales_person'] : $saleOrder->salesPerson,
                jobReferenceNo: isset($data['job_reference_no']) ? (!empty($data['job_reference_no']) ? trim((string)$data['job_reference_no']) : null) : $saleOrder->jobReferenceNo,
                masterAwbNo: isset($data['master_awb_no']) ? (!empty($data['master_awb_no']) ? trim((string)$data['master_awb_no']) : null) : $saleOrder->masterAwbNo,
                shipper: isset($data['shipper']) ? (int)$data['shipper'] : $saleOrder->shipper,
                consignee: isset($data['consignee']) ? (int)$data['consignee'] : $saleOrder->consignee,
                origin: isset($data['origin']) ? (int)$data['origin'] : $saleOrder->origin,
                destination: isset($data['destination']) ? (int)$data['destination'] : $saleOrder->destination,
                noOfPacks: isset($data['no_of_packs']) ? (int)$data['no_of_packs'] : $saleOrder->noOfPacks,
                grossWeight: isset($data['gross_weight']) ? (float)$data['gross_weight'] : $saleOrder->grossWeight,
                chargeableWeight: isset($data['chargeable_weight']) ? (float)$data['chargeable_weight'] : $saleOrder->chargeableWeight,
                volume: isset($data['volume']) ? (float)$data['volume'] : $saleOrder->volume,
                termsAndConditions: isset($data['terms_and_conditions']) ? (!empty($data['terms_and_conditions']) ? trim((string)$data['terms_and_conditions']) : null) : $saleOrder->termsAndConditions,
                grandSubtotal: $grandSubtotal,
                grandDiscountType: isset($data['grand_discount_type']) ? trim((string)$data['grand_discount_type']) : $saleOrder->grandDiscountType,
                grandDiscountTypeValue: isset($data['grand_discount_type_value']) ? (float)$data['grand_discount_type_value'] : $saleOrder->grandDiscountTypeValue,
                grandDiscountAmount: isset($data['grand_discount_amount']) ? (float)$data['grand_discount_amount'] : $saleOrder->grandDiscountAmount,
                grandAfterDiscount: isset($data['grand_after_discount']) ? (float)$data['grand_after_discount'] : $saleOrder->grandAfterDiscount,
                customerNotes: isset($data['customer_notes']) ? (!empty($data['customer_notes']) ? trim((string)$data['customer_notes']) : null) : $saleOrder->customerNotes,
                grandTax: isset($data['grand_tax']) ? (float)$data['grand_tax'] : $saleOrder->grandTax,
                grandTotal: $grandTotal,
                publish: isset($data['publish']) ? (bool)$data['publish'] : $saleOrder->publish,
                isActive: isset($data['is_active']) ? (bool)$data['is_active'] : $saleOrder->publish,
                createdAt: $saleOrder->createdAt,
                createdBy: $saleOrder->createdBy,
                updatedBy: $userId,
                pdf: isset($data['pdf']) ? trim((string)$data['pdf']) : $saleOrder->pdf
            );

            $savedSaleOrder = $this->saleOrderRepo->save($updatedSaleOrder);

            // Fetch existing items to manage changes (updates, inserts, deletions)
            $existingItems = $this->saleOrderRepo->findItemsBySaleOrder($id, $orgId);
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

                $item = new SaleOrderItem(
                    id: $itemId,
                    organizationId: $orgId,
                    saleOrderId: $id,
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
                $this->saleOrderRepo->saveItem($item);
            }

            // Identify and delete removed items
            $deletedIds = array_diff($existingIds, $incomingIds);
            if (!empty($deletedIds)) {
                $this->saleOrderRepo->deleteItemsByIds($deletedIds, $id, $orgId);
            }

            $this->db->commit();

            return $savedSaleOrder;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * List all sale orders in an organization
     */
    public function list(int $orgId): array
    {
        return $this->saleOrderRepo->findAll($orgId);
    }

    /**
     * Delete a sale order and its items
     */
    public function deleteSaleOrder(int $id, int $orgId): bool
    {
        $saleOrder = $this->getSaleOrder($id, $orgId);

        $this->db->beginTransaction();
        try {
            $result = $this->saleOrderRepo->delete($id, $orgId);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Clone a sale order
     */
    public function cloneSaleOrder(int $id, int $orgId, int $userId): SaleOrder
    {
        $saleOrder = $this->getSaleOrder($id, $orgId);
        $items = $this->getSaleOrderItems($id, $orgId);

        $this->db->beginTransaction();
        try {
            // Auto generate new SaleOrder number
            $prefix = 'FL-SO' . date('ym');
            $lastSoNo = $this->saleOrderRepo->getLastSaleOrderNoForMonth($prefix, $orgId);
            if ($lastSoNo !== null) {
                $lastSerial = (int) substr($lastSoNo, -4);
                $newSerial = $lastSerial + 1;
            } else {
                $newSerial = 1;
            }
            $saleOrderNo = $prefix . '-' . str_pad((string)$newSerial, 4, '0', STR_PAD_LEFT);

            $cloned = new SaleOrder(
                id: null,
                organizationId: $orgId,
                saleOrderNo: $saleOrderNo,
                customerId: $saleOrder->customerId,
                saleOrderStatus: 'draft',
                saleOrderDate: date('Y-m-d'),
                expiryDate: date('Y-m-d'),
                referenceNo: $saleOrder->referenceNo,
                warehouseId: $saleOrder->warehouseId,
                expectedShipmentDate: $saleOrder->expectedShipmentDate,
                paymentTerm: $saleOrder->paymentTerm,
                shipmentType: $saleOrder->shipmentType,
                salesPerson: $saleOrder->salesPerson,
                jobReferenceNo: $saleOrder->jobReferenceNo,
                masterAwbNo: $saleOrder->masterAwbNo,
                shipper: $saleOrder->shipper,
                consignee: $saleOrder->consignee,
                origin: $saleOrder->origin,
                destination: $saleOrder->destination,
                noOfPacks: $saleOrder->noOfPacks,
                grossWeight: $saleOrder->grossWeight,
                chargeableWeight: $saleOrder->chargeableWeight,
                volume: $saleOrder->volume,
                termsAndConditions: $saleOrder->termsAndConditions,
                grandSubtotal: $saleOrder->grandSubtotal,
                grandDiscountType: $saleOrder->grandDiscountType,
                grandDiscountTypeValue: $saleOrder->grandDiscountTypeValue,
                grandDiscountAmount: $saleOrder->grandDiscountAmount,
                grandAfterDiscount: $saleOrder->grandAfterDiscount,
                customerNotes: $saleOrder->customerNotes,
                grandTax: $saleOrder->grandTax,
                grandTotal: $saleOrder->grandTotal,
                publish: $saleOrder->publish,
                isActive: $saleOrder->publish,
                createdBy: $userId
            );

            $savedCloned = $this->saleOrderRepo->save($cloned);
            $newSaleOrderId = $savedCloned->id;

            if ($newSaleOrderId === null) {
                throw new \RuntimeException("Failed to clone sale order header.");
            }

            foreach ($items as $item) {
                $clonedItem = new SaleOrderItem(
                    id: null,
                    organizationId: $orgId,
                    saleOrderId: $newSaleOrderId,
                    service: $item->service,
                    description: $item->description,
                    qty: $item->qty,
                    rate: $item->rate,
                    subTotal: $item->subTotal,
                    tax: $item->tax,
                    taxAmount: $item->taxAmount,
                    total: $item->total,
                    discountType: $item->discountType,
                    discountTypeValue: $item->discountTypeValue,
                    discountAmount: $item->discountAmount,
                    createdBy: $userId
                );
                $this->saleOrderRepo->saveItem($clonedItem);
            }

            $this->db->commit();

            return $savedCloned;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update status of a sale order
     */
    public function updateStatus(int $id, string $status, int $orgId): bool
    {
        $allowedStatuses = ['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled', 'confirmed'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new ValidationException(['status' => "Invalid status: {$status}"]);
        }
        return $this->saleOrderRepo->updateStatus($id, $status, $orgId);
    }

    /**
     * Update sale order PDF path
     */
    public function updatePdf(int $id, string $pdfFilename, int $orgId): bool
    {
        return $this->saleOrderRepo->updatePdf($id, $pdfFilename, $orgId);
    }

    /**
     * Validate SaleOrder fields
     *
     * @throws ValidationException
     */
    private function validateSaleOrderData(array $data, int $orgId): void
    {
        if (empty($data['customer_id']) || $data['customer_id'] === 'Please select') {
            throw new ValidationException(['customer_id' => "Please select Customer."]);
        }
        if (empty($data['sale_order_date'])) {
            throw new ValidationException(['sale_order_date' => "Please select Sale Order Date."]);
        }

        // Verify customer exists in organization
        $customerId = (int)$data['customer_id'];
        $customer = $this->customerRepo->find($customerId, $orgId);
        if ($customer === null) {
            throw new ValidationException(['customer_id' => "Selected customer does not exist in your organization."]);
        }
    }
}
