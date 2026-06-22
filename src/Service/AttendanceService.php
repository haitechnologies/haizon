<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Attendance;
use App\Repository\AttendanceRepository;
use App\Exception\ValidationException;

class AttendanceService
{
    private AttendanceRepository $repo;

    public function __construct(AttendanceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getById(int $id): ?Attendance
    {
        return $this->repo->find($id);
    }

    public function list(): array
    {
        return $this->repo->findAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $employeeId = (int)($data['employee_id'] ?? 0);
        $workDate = trim((string)($data['work_date'] ?? ''));

        if ($employeeId <= 0) {
            throw new ValidationException(['employee_id' => 'Employee is mandatory.']);
        }
        if ($workDate === '') {
            throw new ValidationException(['work_date' => 'Work date is mandatory.']);
        }

        $item = new Attendance(
            id: 0,
            employeeId: $employeeId,
            workDate: $workDate,
            checkIn: ($data['check_in'] ?? '') !== '' ? (string)$data['check_in'] : null,
            checkOut: ($data['check_out'] ?? '') !== '' ? (string)$data['check_out'] : null,
            totalHours: (float)($data['total_hours'] ?? 0),
            status: $data['status'] ?? 'present',
            createdBy: $createdBy,
        );

        return $this->repo->insert($item);
    }

    public function update(int $id, array $data, int $updatedBy): bool
    {
        $existing = $this->repo->find($id);
        if ($existing === null) {
            return false;
        }

        $employeeId = (int)($data['employee_id'] ?? $existing->employeeId);
        $workDate = trim((string)($data['work_date'] ?? $existing->workDate));

        if ($employeeId <= 0) {
            throw new ValidationException(['employee_id' => 'Employee is mandatory.']);
        }
        if ($workDate === '') {
            throw new ValidationException(['work_date' => 'Work date is mandatory.']);
        }

        return $this->repo->update($id, [
            'employee_id' => $employeeId,
            'work_date' => $workDate,
            'check_in' => ($data['check_in'] ?? '') !== '' ? (string)$data['check_in'] : null,
            'check_out' => ($data['check_out'] ?? '') !== '' ? (string)$data['check_out'] : null,
            'total_hours' => (float)($data['total_hours'] ?? $existing->totalHours),
            'status' => $data['status'] ?? $existing->status,
            'updated_by' => $updatedBy,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}
