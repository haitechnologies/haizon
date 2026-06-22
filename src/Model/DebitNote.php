<?php

declare(strict_types=1);

namespace App\Model;

/**
 * DebitNote DTO
 *
 * Readonly data transfer object representing a debit note record.
 */
readonly class DebitNote
{
    public function __construct(
        public ?int $id,
        public int $organizationId,
        public string $debitNoteNo,
        public string $debitNoteDate,
        public string $debitNoteStatus,
        public ?string $referenceNo = null,
        public int $vendorId = 0,
        public int $purchaseId = 0,
        public int $warehouseId = 0,
        public int $purchasePerson = 0,
        public ?string $vendorNotes = null,
        public ?string $termsAndConditions = null,
        public float $grandSubtotal = 0.0,
        public string $grandDiscountType = '0.00',
        public float $grandDiscountTypeValue = 0.0,
        public float $grandDiscountAmount = 0.0,
        public float $grandAfterDiscount = 0.0,
        public float $grandTax = 0.0,
        public float $grandTotal = 0.0,
        public bool $publish = true,
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
            'debit_note_no' => $this->debitNoteNo,
            'debit_note_date' => $this->debitNoteDate,
            'debit_note_status' => $this->debitNoteStatus,
            'reference_no' => $this->referenceNo,
            'vendor_id' => $this->vendorId,
            'purchase_id' => $this->purchaseId,
            'warehouse_id' => $this->warehouseId,
            'purchase_person' => $this->purchasePerson,
            'vendor_notes' => $this->vendorNotes,
            'terms_and_conditions' => $this->termsAndConditions,
            'grand_subtotal' => $this->grandSubtotal,
            'grand_discount_type' => $this->grandDiscountType,
            'grand_discount_type_value' => $this->grandDiscountTypeValue,
            'grand_discount_amount' => $this->grandDiscountAmount,
            'grand_after_discount' => $this->grandAfterDiscount,
            'grand_tax' => $this->grandTax,
            'grand_total' => $this->grandTotal,
            'publish' => $this->publish ? 1 : 0,
            'is_active' => $this->isActive ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'updated_by' => $this->updatedBy,
            'created_by' => $this->createdBy,
        ];
    }
}
