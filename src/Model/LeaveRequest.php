<?php

declare(strict_types=1);

namespace App\Model;

/**
 * LeaveRequest DTO
 *
 * Readonly data transfer object representing a leave request record.
 */
readonly class LeaveRequest
{
    public function __construct(
        public ?int $id,
        public ?int $organizationId,
        public int $employeeId,
        public int $leaveTypeId,
        public string $startDate,
        public string $endDate,
        public float $totalDays,
        public float $paidDays = 0,
        public ?string $reason,
        public string $status,
        public bool $medicalReportProvided = false,
        public ?string $medicalReportFile = null,
        public ?int $approvedBy = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null
    ) {
    }

    /**
     * Convert DTO to array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'employee_id' => $this->employeeId,
            'leave_type_id' => $this->leaveTypeId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'total_days' => $this->totalDays,
            'paid_days' => $this->paidDays,
            'reason' => $this->reason,
            'status' => $this->status,
            'medical_report_provided' => $this->medicalReportProvided ? 1 : 0,
            'medical_report_file' => $this->medicalReportFile,
            'approved_by' => $this->approvedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
