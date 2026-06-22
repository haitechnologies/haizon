<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\DB;
use App\Model\ShippingAdvice;
use App\Model\ShippingAdviceItem;
use App\Repository\ShippingAdviceRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Helper\DateHelper;

class ShippingAdviceService
{
    private ShippingAdviceRepository $adviceRepo;
    private Database $db;

    public function __construct(ShippingAdviceRepository $adviceRepo, Database $db)
    {
        $this->adviceRepo = $adviceRepo;
        $this->db = $db;
    }

    public function getAdvice(int $id, int $orgId): ShippingAdvice
    {
        $advice = $this->adviceRepo->find($id, $orgId);
        if ($advice === null) {
            throw new NotFoundException("Shipping Advice with ID {$id} not found.");
        }
        return $advice;
    }

    public function getAdviceItems(int $adviceId, int $orgId): array
    {
        return $this->adviceRepo->findItemsByAdvice($adviceId, $orgId);
    }

    public function createAdvice(array $data, array $itemsData, int $orgId, int $userId): ShippingAdvice
    {
        $this->validateAdviceData($data);

        if (empty($itemsData)) {
            throw new ValidationException(['items' => "No items added. Please add at least one item."]);
        }

        $this->db->beginTransaction();
        try {
            $invoiceDate = $this->parseDate((string)($data['invoice_date'] ?? ''));
            $invoiceNo = $this->generateAdviceNo($orgId);

            $advice = new ShippingAdvice(
                id: null,
                organizationId: $orgId,
                invoiceDate: $invoiceDate,
                invoiceNo: $invoiceNo,
                customerId: (int)($data['customer_id'] ?? 0),
                invoiceStatus: (string)($data['invoice_status'] ?? 'draft'),
                warehouseId: (int)($data['warehouse_id'] ?? 0),
                referenceNo: !empty($data['reference_no']) ? trim((string)$data['reference_no']) : null,
                awbNo: !empty($data['awb_no']) ? trim((string)$data['awb_no']) : null,
                licenseNo: !empty($data['license_no']) ? trim((string)$data['license_no']) : null,
                mirsalIICode: !empty($data['mirsal_II_code']) ? trim((string)$data['mirsal_II_code']) : null,
                grandTotal: (float)($data['grand_total'] ?? 0.0),
                createdBy: $userId,
            );

            $savedAdvice = $this->adviceRepo->save($advice);
            $adviceId = $savedAdvice->id;

            if ($adviceId === null) {
                throw new \RuntimeException("Failed to insert shipping advice header.");
            }

            foreach ($itemsData as $itemData) {
                $item = new ShippingAdviceItem(
                    id: null,
                    organizationId: $orgId,
                    adviceId: $adviceId,
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    coo: (int)($itemData['coo'] ?? 0),
                    declarationNo: !empty($itemData['declaration_no']) ? trim((string)$itemData['declaration_no']) : null,
                    hscode: !empty($itemData['hscode']) ? trim((string)$itemData['hscode']) : null,
                    qty: (int)($itemData['qty'] ?? 1),
                    rate: (float)($itemData['rate'] ?? 0.0),
                    total: (float)($itemData['total'] ?? 0.0),
                    createdBy: $userId,
                );
                $this->adviceRepo->saveItem($item);
            }

            $this->db->commit();

            return $savedAdvice;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateAdvice(int $id, array $data, array $itemsData, int $orgId, int $userId): ShippingAdvice
    {
        $advice = $this->getAdvice($id, $orgId);
        $this->validateAdviceData($data);

        $this->db->beginTransaction();
        try {
            $invoiceDate = isset($data['invoice_date']) ? $this->parseDate((string)$data['invoice_date']) : $advice->invoiceDate;

            $updatedAdvice = new ShippingAdvice(
                id: $advice->id,
                organizationId: $advice->organizationId,
                invoiceDate: $invoiceDate,
                invoiceNo: $advice->invoiceNo,
                customerId: isset($data['customer_id']) ? (int)$data['customer_id'] : $advice->customerId,
                invoiceStatus: isset($data['invoice_status']) ? (string)$data['invoice_status'] : $advice->invoiceStatus,
                warehouseId: isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : $advice->warehouseId,
                referenceNo: isset($data['reference_no']) ? (!empty($data['reference_no']) ? trim((string)$data['reference_no']) : null) : $advice->referenceNo,
                awbNo: isset($data['awb_no']) ? (!empty($data['awb_no']) ? trim((string)$data['awb_no']) : null) : $advice->awbNo,
                licenseNo: isset($data['license_no']) ? (!empty($data['license_no']) ? trim((string)$data['license_no']) : null) : $advice->licenseNo,
                mirsalIICode: isset($data['mirsal_II_code']) ? (!empty($data['mirsal_II_code']) ? trim((string)$data['mirsal_II_code']) : null) : $advice->mirsalIICode,
                grandTotal: isset($data['grand_total']) ? (float)$data['grand_total'] : $advice->grandTotal,
                createdAt: $advice->createdAt,
                createdBy: $advice->createdBy,
                updatedBy: $userId,
            );

            $savedAdvice = $this->adviceRepo->save($updatedAdvice);

            $existingItems = $this->adviceRepo->findItemsByAdvice($id, $orgId);
            $existingIds = array_map(fn($item) => $item->id, $existingItems);
            $incomingIds = [];

            foreach ($itemsData as $itemData) {
                $itemId = !empty($itemData['item_id']) ? (int)$itemData['item_id'] : null;

                if ($itemId !== null) {
                    $incomingIds[] = $itemId;
                }

                $item = new ShippingAdviceItem(
                    id: $itemId,
                    organizationId: $orgId,
                    adviceId: $id,
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    coo: (int)($itemData['coo'] ?? 0),
                    declarationNo: !empty($itemData['declaration_no']) ? trim((string)$itemData['declaration_no']) : null,
                    hscode: !empty($itemData['hscode']) ? trim((string)$itemData['hscode']) : null,
                    qty: (int)($itemData['qty'] ?? 1),
                    rate: (float)($itemData['rate'] ?? 0.0),
                    total: (float)($itemData['total'] ?? 0.0),
                    createdBy: $userId,
                );
                $this->adviceRepo->saveItem($item);
            }

            $deletedIds = array_diff($existingIds, $incomingIds);
            if (!empty($deletedIds)) {
                $this->adviceRepo->deleteItemsByIds($deletedIds, $id, $orgId);
            }

            $this->db->commit();

            return $savedAdvice;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteAdvice(int $id, int $orgId): bool
    {
        $this->getAdvice($id, $orgId);

        $this->db->beginTransaction();
        try {
            $result = $this->adviceRepo->delete($id, $orgId);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function generateAdviceNo(int $orgId): string
    {
        $prefix = 'SA' . date('ym');
        $lastNo = $this->adviceRepo->getLastAdviceNoForMonth($orgId, $prefix);

        if ($lastNo !== null) {
            $lastSerial = (int)substr($lastNo, -4);
            $newSerial = $lastSerial + 1;
        } else {
            $newSerial = 1;
        }

        return $prefix . '-' . str_pad((string)$newSerial, 4, '0', STR_PAD_LEFT);
    }

    private function validateAdviceData(array $data): void
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
