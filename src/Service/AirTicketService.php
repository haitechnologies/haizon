<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\DB;
use App\Model\AirTicket;
use App\Repository\AirTicketRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

/**
 * AirTicket Service
 *
 * Implements business logic for managing air ticket entitlements.
 */
class AirTicketService
{
    private AirTicketRepository $repo;
    private Database $db;

    public function __construct(AirTicketRepository $repo, Database $db)
    {
        $this->repo = $repo;
        $this->db = $db;
    }

    /**
     * Get an air ticket by ID
     */
    public function getById(int $id, int $organizationId): AirTicket
    {
        $ticket = $this->repo->find($id, $organizationId);
        if ($ticket === null) {
            throw new NotFoundException("Air Ticket with ID {$id} not found.");
        }
        return $ticket;
    }

    /**
     * List all air tickets
     *
     * @return AirTicket[]
     */
    public function list(?int $orgId = null): array
    {
        return $this->repo->findAll($orgId);
    }

    /**
     * Get air tickets by employee
     *
     * @return AirTicket[]
     */
    public function getByEmployee(int $employeeId, ?int $organizationId = null): array
    {
        return $this->repo->findByEmployee($employeeId, $organizationId);
    }

    /**
     * Create a new air ticket record
     */
    public function create(array $data, int $createdBy, int $organizationId): int
    {
        if (empty($data['employee_id'])) {
            throw new ValidationException(['employee_id' => 'Employee is required.']);
        }

        $ticket = new AirTicket(
            id: 0,
            organizationId: $organizationId,
            employeeId: (int)$data['employee_id'],
            entitlementAmount: (float)($data['entitlement_amount'] ?? 1250.00),
            status: $data['status'] ?? 'pending',
            eligibilityDate: $data['eligibility_date'] ?? null,
            paidDate: $data['paid_date'] ?? null,
            departureDate: $data['departure_date'] ?? null,
            arrivalDate: $data['arrival_date'] ?? null,
            ticketFile: $data['ticket_file'] ?? null,
            paymentReference: (string)($data['payment_reference'] ?? ''),
            notes: (string)($data['notes'] ?? ''),
            createdBy: $createdBy,
        );

        return $this->repo->insert($ticket);
    }

    /**
     * Update an existing air ticket record
     *
     * Only allows updating: status, paid_date, payment_reference, notes
     */
    public function update(int $id, array $data, int $updatedBy, int $organizationId): bool
    {
        // Verify the ticket exists
        $this->getById($id, $organizationId);

        // Only allow specific fields to be updated
        $allowedFields = ['status', 'paid_date', 'payment_reference', 'notes', 'departure_date', 'arrival_date', 'ticket_file'];
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            throw new ValidationException(['update' => 'No valid fields provided for update.']);
        }

        return $this->repo->update($id, $updateData, $organizationId);
    }

    /**
     * Delete an air ticket record
     */
    public function delete(int $id, int $organizationId): bool
    {
        $this->getById($id, $organizationId);
        return $this->repo->delete($id, $organizationId);
    }

    /**
     * Calculate eligible employees for air ticket generation
     *
     * Finds employees hired 12+ months ago who don't have an active (non-cancelled) air ticket.
     * Uses DB::USERS table, joining on employee_id = user.id.
     *
     * @return array Array of eligible user records (id, full_name, date_of_joining)
     */
    public function calculateEligibleEmployees(int $organizationId): array
    {
        $sql = "SELECT u.id, u.full_name, u.date_of_joining
                FROM " . DB::USERS . " u
                WHERE u.organization_id = :organization_id
                  AND u.is_active = 1
                  AND u.date_of_joining IS NOT NULL
                  AND u.date_of_joining <= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  AND u.id NOT IN (
                      SELECT at2.employee_id
                      FROM " . DB::AIR_TICKETS . " at2
                      WHERE at2.organization_id = :org_id2
                        AND at2.status != 'cancelled'
                  )
                ORDER BY u.full_name ASC";

        return $this->db->fetchAll($sql, [
            'organization_id' => $organizationId,
            'org_id2' => $organizationId,
        ]);
    }
}
