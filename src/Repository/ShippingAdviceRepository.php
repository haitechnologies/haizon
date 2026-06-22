<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\ShippingAdvice;
use App\Model\ShippingAdviceItem;

class ShippingAdviceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id, int $orgId): ?ShippingAdvice
    {
        $sql = "SELECT * FROM `{DB::SHIPPING_ADVICES}` WHERE id = :id AND organization_id = :org_id";
        $row = $this->db->fetchOne($sql, ['id' => $id, 'org_id' => $orgId]);
        if ($row === null) {
            return null;
        }
        return $this->mapRowToShippingAdvice($row);
    }

    public function findItemsByAdvice(int $adviceId, int $orgId): array
    {
        $sql = "SELECT * FROM `{DB::SHIPPING_ADVICE_ITEMS}` WHERE advice_id = :advice_id AND organization_id = :org_id ORDER BY id ASC";
        $rows = $this->db->fetchAll($sql, ['advice_id' => $adviceId, 'org_id' => $orgId]);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRowToShippingAdviceItem($row);
        }
        return $items;
    }

    public function save(ShippingAdvice $advice): ShippingAdvice
    {
        if ($advice->id === null) {
            return $this->insert($advice);
        }
        return $this->update($advice);
    }

    private function insert(ShippingAdvice $advice): ShippingAdvice
    {
        $sql = "INSERT INTO `{DB::SHIPPING_ADVICES}` (
                    organization_id, invoice_date, invoice_no, customer_id, invoice_status,
                    warehouse_id, reference_no, awb_no, license_no, mirsal_II_code,
                    grand_total, is_active, created_at, updated_at, created_by
                ) VALUES (
                    :organization_id, :invoice_date, :invoice_no, :customer_id, :invoice_status,
                    :warehouse_id, :reference_no, :awb_no, :license_no, :mirsal_II_code,
                    :grand_total, :is_active, NOW(), NOW(), :created_by
                )";

        $params = $advice->toArray();
        unset($params['id'], $params['created_at'], $params['updated_at'], $params['updated_by']);

        $insertId = (int)$this->db->insert($sql, $params);

        $inserted = $this->find($insertId, $advice->organizationId);
        if ($inserted === null) {
            throw new \RuntimeException("Failed to retrieve inserted shipping advice.");
        }

        return $inserted;
    }

    private function update(ShippingAdvice $advice): ShippingAdvice
    {
        $sql = "UPDATE `{DB::SHIPPING_ADVICES}` SET
                    invoice_date = :invoice_date,
                    invoice_no = :invoice_no,
                    customer_id = :customer_id,
                    invoice_status = :invoice_status,
                    warehouse_id = :warehouse_id,
                    reference_no = :reference_no,
                    awb_no = :awb_no,
                    license_no = :license_no,
                    mirsal_II_code = :mirsal_II_code,
                    grand_total = :grand_total,
                    is_active = :is_active,
                    updated_at = NOW(),
                    updated_by = :updated_by
                WHERE id = :id AND organization_id = :organization_id";

        $params = $advice->toArray();
        unset($params['created_at'], $params['updated_at'], $params['created_by']);

        $this->db->execute($sql, $params);

        $updated = $this->find((int)$advice->id, $advice->organizationId);
        if ($updated === null) {
            throw new \RuntimeException("Failed to retrieve updated shipping advice.");
        }

        return $updated;
    }

    public function saveItem(ShippingAdviceItem $item): ShippingAdviceItem
    {
        if ($item->id === null) {
            return $this->insertItem($item);
        }
        return $this->updateItem($item);
    }

    private function insertItem(ShippingAdviceItem $item): ShippingAdviceItem
    {
        $sql = "INSERT INTO `{DB::SHIPPING_ADVICE_ITEMS}` (
                    organization_id, advice_id, description, coo, declaration_no, hscode,
                    qty, rate, total, created_at, updated_at, created_by
                ) VALUES (
                    :organization_id, :advice_id, :description, :coo, :declaration_no, :hscode,
                    :qty, :rate, :total, NOW(), NOW(), :created_by
                )";

        $params = $item->toArray();
        unset($params['id'], $params['created_at'], $params['updated_at']);

        $insertId = (int)$this->db->insert($sql, $params);

        $inserted = $this->findItem($insertId, $item->organizationId);
        if ($inserted === null) {
            throw new \RuntimeException("Failed to retrieve inserted shipping advice item.");
        }

        return $inserted;
    }

    private function updateItem(ShippingAdviceItem $item): ShippingAdviceItem
    {
        $sql = "UPDATE `{DB::SHIPPING_ADVICE_ITEMS}` SET
                    description = :description,
                    coo = :coo,
                    declaration_no = :declaration_no,
                    hscode = :hscode,
                    qty = :qty,
                    rate = :rate,
                    total = :total,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :organization_id";

        $params = $item->toArray();
        unset($params['advice_id'], $params['created_at'], $params['updated_at'], $params['created_by']);

        $this->db->execute($sql, $params);

        $updated = $this->findItem((int)$item->id, $item->organizationId);
        if ($updated === null) {
            throw new \RuntimeException("Failed to retrieve updated shipping advice item.");
        }

        return $updated;
    }

    public function findItem(int $id, int $orgId): ?ShippingAdviceItem
    {
        $sql = "SELECT * FROM `{DB::SHIPPING_ADVICE_ITEMS}` WHERE id = :id AND organization_id = :org_id";
        $row = $this->db->fetchOne($sql, ['id' => $id, 'org_id' => $orgId]);
        if ($row === null) {
            return null;
        }
        return $this->mapRowToShippingAdviceItem($row);
    }

    public function delete(int $id, int $orgId): bool
    {
        $this->deleteItemsByAdvice($id, $orgId);
        $sql = "DELETE FROM `{DB::SHIPPING_ADVICES}` WHERE id = :id AND organization_id = :org_id";
        $stmt = $this->db->execute($sql, ['id' => $id, 'org_id' => $orgId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteItemsByAdvice(int $adviceId, int $orgId): bool
    {
        $sql = "DELETE FROM `{DB::SHIPPING_ADVICE_ITEMS}` WHERE advice_id = :advice_id AND organization_id = :org_id";
        $this->db->execute($sql, ['advice_id' => $adviceId, 'org_id' => $orgId]);
        return true;
    }

    public function deleteItemsByIds(array $ids, int $adviceId, int $orgId): void
    {
        if (empty($ids)) {
            return;
        }
        $placeholders = [];
        $params = ['advice_id' => $adviceId, 'org_id' => $orgId];
        foreach ($ids as $index => $id) {
            $key = 'id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = (int)$id;
        }
        $inClause = implode(', ', $placeholders);
        $sql = "DELETE FROM `{DB::SHIPPING_ADVICE_ITEMS}` 
                WHERE id IN ($inClause) AND advice_id = :advice_id AND organization_id = :org_id";
        $this->db->execute($sql, $params);
    }

    public function getLastAdviceNoForMonth(int $orgId, string $prefix): ?string
    {
        $sql = "SELECT invoice_no FROM `{DB::SHIPPING_ADVICES}` 
                WHERE organization_id = :org_id AND invoice_no LIKE :like_str 
                ORDER BY invoice_no DESC LIMIT 1";
        $row = $this->db->fetchOne($sql, [
            'org_id' => $orgId,
            'like_str' => $prefix . '-%',
        ]);
        return $row !== null ? (string)$row['invoice_no'] : null;
    }

    private function mapRowToShippingAdvice(array $row): ShippingAdvice
    {
        return new ShippingAdvice(
            id: (int)$row['id'],
            organizationId: (int)$row['organization_id'],
            invoiceDate: (string)$row['invoice_date'],
            invoiceNo: $row['invoice_no'] !== null ? (string)$row['invoice_no'] : null,
            customerId: (int)($row['customer_id'] ?? 0),
            invoiceStatus: (string)($row['invoice_status'] ?? 'draft'),
            warehouseId: (int)($row['warehouse_id'] ?? 0),
            referenceNo: $row['reference_no'] !== null ? (string)$row['reference_no'] : null,
            awbNo: $row['awb_no'] !== null ? (string)$row['awb_no'] : null,
            licenseNo: $row['license_no'] !== null ? (string)$row['license_no'] : null,
            mirsalIICode: $row['mirsal_II_code'] !== null ? (string)$row['mirsal_II_code'] : null,
            grandTotal: (float)($row['grand_total'] ?? 0.0),
            isActive: (bool)($row['is_active'] ?? true),
            createdAt: $row['created_at'] !== null ? (string)$row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            updatedBy: $row['updated_by'] !== null ? (int)$row['updated_by'] : null,
            createdBy: (int)($row['created_by'] ?? 0),
        );
    }

    private function mapRowToShippingAdviceItem(array $row): ShippingAdviceItem
    {
        return new ShippingAdviceItem(
            id: (int)$row['id'],
            organizationId: (int)($row['organization_id'] ?? 0),
            adviceId: (int)$row['advice_id'],
            description: $row['description'] !== null ? (string)$row['description'] : null,
            coo: (int)($row['coo'] ?? 0),
            declarationNo: $row['declaration_no'] !== null ? (string)$row['declaration_no'] : null,
            hscode: $row['hscode'] !== null ? (string)$row['hscode'] : null,
            qty: (int)($row['qty'] ?? 1),
            rate: (float)($row['rate'] ?? 0.0),
            total: (float)($row['total'] ?? 0.0),
            createdAt: $row['created_at'] !== null ? (string)$row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            createdBy: (int)($row['created_by'] ?? 0),
        );
    }
}
