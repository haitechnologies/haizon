<?php

declare(strict_types=1);

namespace App\Model;

readonly class ShippingAdvice
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public string $invoiceDate,
        public ?string $invoiceNo,
        public int $customerId,
        public string $invoiceStatus,
        public int $warehouseId,
        public ?string $referenceNo,
        public ?string $awbNo,
        public ?string $licenseNo,
        public ?string $mirsalIICode,
        public float $grandTotal,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?int $updatedBy = null,
        public int $createdBy = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'invoice_date' => $this->invoiceDate,
            'invoice_no' => $this->invoiceNo,
            'customer_id' => $this->customerId,
            'invoice_status' => $this->invoiceStatus,
            'warehouse_id' => $this->warehouseId,
            'reference_no' => $this->referenceNo,
            'awb_no' => $this->awbNo,
            'license_no' => $this->licenseNo,
            'mirsal_II_code' => $this->mirsalIICode,
            'grand_total' => $this->grandTotal,
            'is_active' => $this->isActive ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'updated_by' => $this->updatedBy,
            'created_by' => $this->createdBy,
        ];
    }
}
