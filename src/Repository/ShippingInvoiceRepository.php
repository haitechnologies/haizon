<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DB;
use App\Model\ShippingInvoice;
use App\Model\ShippingInvoiceItem;

class ShippingInvoiceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id, int $orgId): ?ShippingInvoice
    {
        $sql = "SELECT * FROM `{DB::SHIPPING_INVOICES}` WHERE id = :id AND organization_id = :org_id";
        $row = $this->db->fetchOne($sql, ['id' => $id, 'org_id' => $orgId]);
        if ($row === null) {
            return null;
        }
        return $this->mapRowToShippingInvoice($row);
    }

    public function findItemsByInvoice(int $invoiceId, int $orgId): array
    {
        $sql = "SELECT * FROM `{DB::SHIPPING_INVOICE_ITEMS}` WHERE shipping_invoice_id = :invoice_id AND organization_id = :org_id ORDER BY id ASC";
        $rows = $this->db->fetchAll($sql, ['invoice_id' => $invoiceId, 'org_id' => $orgId]);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRowToShippingInvoiceItem($row);
        }
        return $items;
    }

    public function save(ShippingInvoice $invoice): ShippingInvoice
    {
        if ($invoice->id === null) {
            return $this->insert($invoice);
        }
        return $this->update($invoice);
    }

    private function insert(ShippingInvoice $invoice): ShippingInvoice
    {
        $sql = "INSERT INTO `{DB::SHIPPING_INVOICES}` (
                    organization_id, invoice_date, invoice_no, reference_no, customer_id,
                    invoice_status, warehouse_id, no_of_packs, gross_weight, master_awb_no,
                    grand_total, is_active, created_at, updated_at, created_by
                ) VALUES (
                    :organization_id, :invoice_date, :invoice_no, :reference_no, :customer_id,
                    :invoice_status, :warehouse_id, :no_of_packs, :gross_weight, :master_awb_no,
                    :grand_total, :is_active, NOW(), NOW(), :created_by
                )";

        $params = $invoice->toArray();
        unset($params['id'], $params['created_at'], $params['updated_at'], $params['updated_by']);

        $insertId = (int)$this->db->insert($sql, $params);

        $inserted = $this->find($insertId, $invoice->organizationId);
        if ($inserted === null) {
            throw new \RuntimeException("Failed to retrieve inserted shipping invoice.");
        }

        return $inserted;
    }

    private function update(ShippingInvoice $invoice): ShippingInvoice
    {
        $sql = "UPDATE `{DB::SHIPPING_INVOICES}` SET
                    invoice_date = :invoice_date,
                    invoice_no = :invoice_no,
                    reference_no = :reference_no,
                    customer_id = :customer_id,
                    invoice_status = :invoice_status,
                    warehouse_id = :warehouse_id,
                    no_of_packs = :no_of_packs,
                    gross_weight = :gross_weight,
                    master_awb_no = :master_awb_no,
                    grand_total = :grand_total,
                    is_active = :is_active,
                    updated_at = NOW(),
                    updated_by = :updated_by
                WHERE id = :id AND organization_id = :organization_id";

        $params = $invoice->toArray();
        unset($params['created_at'], $params['updated_at'], $params['created_by']);

        $this->db->execute($sql, $params);

        $updated = $this->find((int)$invoice->id, $invoice->organizationId);
        if ($updated === null) {
            throw new \RuntimeException("Failed to retrieve updated shipping invoice.");
        }

        return $updated;
    }

    public function saveItem(ShippingInvoiceItem $item): ShippingInvoiceItem
    {
        if ($item->id === null) {
            return $this->insertItem($item);
        }
        return $this->updateItem($item);
    }

    private function insertItem(ShippingInvoiceItem $item): ShippingInvoiceItem
    {
        $sql = "INSERT INTO `{DB::SHIPPING_INVOICE_ITEMS}` (
                    organization_id, shipping_invoice_id, description, origin, declaration_no,
                    hs_code, qty, unit_price, total_amount, created_at, updated_at, created_by
                ) VALUES (
                    :organization_id, :shipping_invoice_id, :description, :origin, :declaration_no,
                    :hs_code, :qty, :unit_price, :total_amount, NOW(), NOW(), :created_by
                )";

        $params = $item->toArray();
        unset($params['id'], $params['created_at'], $params['updated_at']);

        $insertId = (int)$this->db->insert($sql, $params);

        $inserted = $this->findItem($insertId, $item->organizationId);
        if ($inserted === null) {
            throw new \RuntimeException("Failed to retrieve inserted shipping invoice item.");
        }

        return $inserted;
    }

    private function updateItem(ShippingInvoiceItem $item): ShippingInvoiceItem
    {
        $sql = "UPDATE `{DB::SHIPPING_INVOICE_ITEMS}` SET
                    description = :description,
                    origin = :origin,
                    declaration_no = :declaration_no,
                    hs_code = :hs_code,
                    qty = :qty,
                    unit_price = :unit_price,
                    total_amount = :total_amount,
                    updated_at = NOW()
                WHERE id = :id AND organization_id = :organization_id";

        $params = $item->toArray();
        unset($params['shipping_invoice_id'], $params['created_at'], $params['updated_at'], $params['created_by']);

        $this->db->execute($sql, $params);

        $updated = $this->findItem((int)$item->id, $item->organizationId);
        if ($updated === null) {
            throw new \RuntimeException("Failed to retrieve updated shipping invoice item.");
        }

        return $updated;
    }

    public function findItem(int $id, int $orgId): ?ShippingInvoiceItem
    {
        $sql = "SELECT * FROM `{DB::SHIPPING_INVOICE_ITEMS}` WHERE id = :id AND organization_id = :org_id";
        $row = $this->db->fetchOne($sql, ['id' => $id, 'org_id' => $orgId]);
        if ($row === null) {
            return null;
        }
        return $this->mapRowToShippingInvoiceItem($row);
    }

    public function delete(int $id, int $orgId): bool
    {
        $this->deleteItemsByInvoice($id, $orgId);
        $sql = "DELETE FROM `{DB::SHIPPING_INVOICES}` WHERE id = :id AND organization_id = :org_id";
        $stmt = $this->db->execute($sql, ['id' => $id, 'org_id' => $orgId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteItemsByInvoice(int $invoiceId, int $orgId): bool
    {
        $sql = "DELETE FROM `{DB::SHIPPING_INVOICE_ITEMS}` WHERE shipping_invoice_id = :invoice_id AND organization_id = :org_id";
        $this->db->execute($sql, ['invoice_id' => $invoiceId, 'org_id' => $orgId]);
        return true;
    }

    public function deleteItemsByIds(array $ids, int $invoiceId, int $orgId): void
    {
        if (empty($ids)) {
            return;
        }
        $placeholders = [];
        $params = ['invoice_id' => $invoiceId, 'org_id' => $orgId];
        foreach ($ids as $index => $id) {
            $key = 'id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = (int)$id;
        }
        $inClause = implode(', ', $placeholders);
        $sql = "DELETE FROM `{DB::SHIPPING_INVOICE_ITEMS}` 
                WHERE id IN ($inClause) AND shipping_invoice_id = :invoice_id AND organization_id = :org_id";
        $this->db->execute($sql, $params);
    }

    public function getLastInvoiceNoForMonth(int $orgId, string $prefix): ?string
    {
        $sql = "SELECT invoice_no FROM `{DB::SHIPPING_INVOICES}` 
                WHERE organization_id = :org_id AND invoice_no LIKE :like_str 
                ORDER BY invoice_no DESC LIMIT 1";
        $row = $this->db->fetchOne($sql, [
            'org_id' => $orgId,
            'like_str' => $prefix . '-%',
        ]);
        return $row !== null ? (string)$row['invoice_no'] : null;
    }

    private function mapRowToShippingInvoice(array $row): ShippingInvoice
    {
        return new ShippingInvoice(
            id: (int)$row['id'],
            organizationId: (int)$row['organization_id'],
            invoiceDate: (string)$row['invoice_date'],
            invoiceNo: $row['invoice_no'] !== null ? (string)$row['invoice_no'] : null,
            referenceNo: $row['reference_no'] !== null ? (string)$row['reference_no'] : null,
            customerId: (int)($row['customer_id'] ?? 0),
            invoiceStatus: (string)($row['invoice_status'] ?? 'draft'),
            warehouseId: (int)($row['warehouse_id'] ?? 0),
            noOfPacks: (string)($row['no_of_packs'] ?? ''),
            grossWeight: (string)($row['gross_weight'] ?? ''),
            masterAwbNo: (string)($row['master_awb_no'] ?? ''),
            grandTotal: (float)($row['grand_total'] ?? 0.0),
            isActive: (bool)($row['is_active'] ?? true),
            createdAt: $row['created_at'] !== null ? (string)$row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            updatedBy: $row['updated_by'] !== null ? (int)$row['updated_by'] : null,
            createdBy: (int)($row['created_by'] ?? 0),
        );
    }

    private function mapRowToShippingInvoiceItem(array $row): ShippingInvoiceItem
    {
        return new ShippingInvoiceItem(
            id: (int)$row['id'],
            organizationId: (int)($row['organization_id'] ?? 0),
            shippingInvoiceId: (int)$row['shipping_invoice_id'],
            description: $row['description'] !== null ? (string)$row['description'] : null,
            origin: (int)($row['origin'] ?? 0),
            declarationNo: $row['declaration_no'] !== null ? (string)$row['declaration_no'] : null,
            hsCode: $row['hs_code'] !== null ? (string)$row['hs_code'] : null,
            qty: (int)($row['qty'] ?? 1),
            unitPrice: (float)($row['unit_price'] ?? 0.0),
            totalAmount: (float)($row['total_amount'] ?? 0.0),
            createdAt: $row['created_at'] !== null ? (string)$row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            createdBy: (int)($row['created_by'] ?? 0),
        );
    }
}
