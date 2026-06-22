<?php

declare(strict_types=1);

namespace App\Model;

readonly class ShippingInvoiceItem
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public int $shippingInvoiceId,
        public ?string $description = null,
        public int $origin = 0,
        public ?string $declarationNo = null,
        public ?string $hsCode = null,
        public int $qty = 1,
        public float $unitPrice = 0.0,
        public float $totalAmount = 0.0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public int $createdBy = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'shipping_invoice_id' => $this->shippingInvoiceId,
            'description' => $this->description,
            'origin' => $this->origin,
            'declaration_no' => $this->declarationNo,
            'hs_code' => $this->hsCode,
            'qty' => $this->qty,
            'unit_price' => $this->unitPrice,
            'total_amount' => $this->totalAmount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
        ];
    }
}
