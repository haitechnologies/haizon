<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\DB;
use App\Model\ShippingInvoice;
use App\Model\ShippingInvoiceItem;
use App\Repository\ShippingInvoiceRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Helper\DateHelper;

class ShippingInvoiceService
{
    private ShippingInvoiceRepository $invoiceRepo;
    private Database $db;

    public function __construct(ShippingInvoiceRepository $invoiceRepo, Database $db)
    {
        $this->invoiceRepo = $invoiceRepo;
        $this->db = $db;
    }

    public function getInvoice(int $id, int $orgId): ShippingInvoice
    {
        $invoice = $this->invoiceRepo->find($id, $orgId);
        if ($invoice === null) {
            throw new NotFoundException("Shipping Invoice with ID {$id} not found.");
        }
        return $invoice;
    }

    public function getInvoiceItems(int $invoiceId, int $orgId): array
    {
        return $this->invoiceRepo->findItemsByInvoice($invoiceId, $orgId);
    }

    public function createInvoice(array $data, array $itemsData, int $orgId, int $userId): ShippingInvoice
    {
        $this->validateInvoiceData($data);

        if (empty($itemsData)) {
            throw new ValidationException(['items' => "No items added. Please add at least one item."]);
        }

        $this->db->beginTransaction();
        try {
            $invoiceDate = $this->parseDate((string)($data['invoice_date'] ?? ''));
            $invoiceNo = $this->generateInvoiceNo($orgId);

            $invoice = new ShippingInvoice(
                id: null,
                organizationId: $orgId,
                invoiceDate: $invoiceDate,
                invoiceNo: $invoiceNo,
                referenceNo: !empty($data['reference_no']) ? trim((string)$data['reference_no']) : null,
                customerId: (int)($data['customer_id'] ?? 0),
                invoiceStatus: (string)($data['invoice_status'] ?? 'draft'),
                warehouseId: (int)($data['warehouse_id'] ?? 0),
                noOfPacks: (string)($data['no_of_packs'] ?? ''),
                grossWeight: (string)($data['gross_weight'] ?? ''),
                masterAwbNo: (string)($data['master_awb_no'] ?? ''),
                grandTotal: (float)($data['grand_total'] ?? 0.0),
                createdBy: $userId,
            );

            $savedInvoice = $this->invoiceRepo->save($invoice);
            $invoiceId = $savedInvoice->id;

            if ($invoiceId === null) {
                throw new \RuntimeException("Failed to insert shipping invoice header.");
            }

            foreach ($itemsData as $itemData) {
                $item = new ShippingInvoiceItem(
                    id: null,
                    organizationId: $orgId,
                    shippingInvoiceId: $invoiceId,
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    origin: (int)($itemData['origin'] ?? 0),
                    declarationNo: !empty($itemData['declaration_no']) ? trim((string)$itemData['declaration_no']) : null,
                    hsCode: !empty($itemData['hs_code']) ? trim((string)$itemData['hs_code']) : null,
                    qty: (int)($itemData['qty'] ?? 1),
                    unitPrice: (float)($itemData['unit_price'] ?? 0.0),
                    totalAmount: (float)($itemData['total_amount'] ?? 0.0),
                    createdBy: $userId,
                );
                $this->invoiceRepo->saveItem($item);
            }

            $this->db->commit();

            return $savedInvoice;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateInvoice(int $id, array $data, array $itemsData, int $orgId, int $userId): ShippingInvoice
    {
        $invoice = $this->getInvoice($id, $orgId);
        $this->validateInvoiceData($data);

        $this->db->beginTransaction();
        try {
            $invoiceDate = isset($data['invoice_date']) ? $this->parseDate((string)$data['invoice_date']) : $invoice->invoiceDate;

            $updatedInvoice = new ShippingInvoice(
                id: $invoice->id,
                organizationId: $invoice->organizationId,
                invoiceDate: $invoiceDate,
                invoiceNo: $invoice->invoiceNo,
                referenceNo: isset($data['reference_no']) ? (!empty($data['reference_no']) ? trim((string)$data['reference_no']) : null) : $invoice->referenceNo,
                customerId: isset($data['customer_id']) ? (int)$data['customer_id'] : $invoice->customerId,
                invoiceStatus: isset($data['invoice_status']) ? (string)$data['invoice_status'] : $invoice->invoiceStatus,
                warehouseId: isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : $invoice->warehouseId,
                noOfPacks: isset($data['no_of_packs']) ? (string)$data['no_of_packs'] : $invoice->noOfPacks,
                grossWeight: isset($data['gross_weight']) ? (string)$data['gross_weight'] : $invoice->grossWeight,
                masterAwbNo: isset($data['master_awb_no']) ? (string)$data['master_awb_no'] : $invoice->masterAwbNo,
                grandTotal: isset($data['grand_total']) ? (float)$data['grand_total'] : $invoice->grandTotal,
                createdAt: $invoice->createdAt,
                createdBy: $invoice->createdBy,
                updatedBy: $userId,
            );

            $savedInvoice = $this->invoiceRepo->save($updatedInvoice);

            $existingItems = $this->invoiceRepo->findItemsByInvoice($id, $orgId);
            $existingIds = array_map(fn($item) => $item->id, $existingItems);
            $incomingIds = [];

            foreach ($itemsData as $itemData) {
                $itemId = !empty($itemData['item_id']) ? (int)$itemData['item_id'] : null;

                if ($itemId !== null) {
                    $incomingIds[] = $itemId;
                }

                $item = new ShippingInvoiceItem(
                    id: $itemId,
                    organizationId: $orgId,
                    shippingInvoiceId: $id,
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    origin: (int)($itemData['origin'] ?? 0),
                    declarationNo: !empty($itemData['declaration_no']) ? trim((string)$itemData['declaration_no']) : null,
                    hsCode: !empty($itemData['hs_code']) ? trim((string)$itemData['hs_code']) : null,
                    qty: (int)($itemData['qty'] ?? 1),
                    unitPrice: (float)($itemData['unit_price'] ?? 0.0),
                    totalAmount: (float)($itemData['total_amount'] ?? 0.0),
                    createdBy: $userId,
                );
                $this->invoiceRepo->saveItem($item);
            }

            $deletedIds = array_diff($existingIds, $incomingIds);
            if (!empty($deletedIds)) {
                $this->invoiceRepo->deleteItemsByIds($deletedIds, $id, $orgId);
            }

            $this->db->commit();

            return $savedInvoice;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteInvoice(int $id, int $orgId): bool
    {
        $this->getInvoice($id, $orgId);

        $this->db->beginTransaction();
        try {
            $result = $this->invoiceRepo->delete($id, $orgId);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function generateInvoiceNo(int $orgId): string
    {
        $prefix = 'FL-INS' . date('ym');
        $lastNo = $this->invoiceRepo->getLastInvoiceNoForMonth($orgId, $prefix);

        if ($lastNo !== null) {
            $lastSerial = (int)substr($lastNo, -4);
            $newSerial = $lastSerial + 1;
        } else {
            $newSerial = 1;
        }

        return $prefix . '-' . str_pad((string)$newSerial, 4, '0', STR_PAD_LEFT);
    }

    private function validateInvoiceData(array $data): void
    {
        if (empty($data['invoice_date'])) {
            throw new ValidationException(['invoice_date' => "Please select Invoice Date."]);
        }
        if (empty($data['customer_id']) || (int)$data['customer_id'] <= 0) {
            throw new ValidationException(['customer_id' => "Please select Customer."]);
        }
        if (empty($data['warehouse_id']) || (int)$data['warehouse_id'] <= 0) {
            throw new ValidationException(['warehouse_id' => "Please select Warehouse."]);
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
