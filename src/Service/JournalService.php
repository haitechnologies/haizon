<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Model\Journal;
use App\Model\JournalItem;
use App\Repository\JournalRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Helper\DateHelper;

class JournalService
{
    private JournalRepository $journalRepo;
    private Database $db;

    public function __construct(JournalRepository $journalRepo, Database $db)
    {
        $this->journalRepo = $journalRepo;
        $this->db = $db;
    }

    public function getJournal(int $id, int $orgId): Journal
    {
        $journal = $this->journalRepo->find($id, $orgId);
        if ($journal === null) {
            throw new NotFoundException("Journal with ID {$id} not found.");
        }
        return $journal;
    }

    public function getJournalItems(int $journalId, int $orgId): array
    {
        return $this->journalRepo->findItemsByJournal($journalId, $orgId);
    }

    public function createJournal(array $data, array $itemsData, int $orgId, int $userId): Journal
    {
        $this->validateJournalData($data);
        $this->validateBalance($itemsData);

        $this->db->beginTransaction();
        try {
            $journalDate = $this->parseDate((string)($data['journal_date'] ?? ''));
            $journalNo = $this->generateJournalNo($orgId);

            $debits = $this->sumDebits($itemsData);
            $credits = $this->sumCredits($itemsData);

            $journal = new Journal(
                id: null,
                organizationId: $orgId,
                journalStatus: (string)($data['journal_status'] ?? 'draft'),
                journalNo: $journalNo,
                journalDate: $journalDate,
                referenceNo: !empty($data['reference_no']) ? trim((string)$data['reference_no']) : null,
                notes: trim((string)($data['notes'] ?? '')),
                reportingMethod: (string)($data['reporting_method'] ?? 'accrual'),
                referenceType: !empty($data['reference_type']) ? (string)$data['reference_type'] : null,
                referenceId: (int)($data['reference_id'] ?? 0),
                currency: (string)($data['currency'] ?? 'AED'),
                grandSubtotal: $debits,
                grandTotal: $credits,
                warehouseId: (int)($data['warehouse_id'] ?? 0),
                createdBy: $userId,
            );

            $savedJournal = $this->journalRepo->save($journal);
            $journalId = $savedJournal->id;

            if ($journalId === null) {
                throw new \RuntimeException("Failed to insert journal header.");
            }

            foreach ($itemsData as $itemData) {
                $account = (int)($itemData['account'] ?? 0);
                $debit = (float)($itemData['debit'] ?? 0.0);
                $credit = (float)($itemData['credit'] ?? 0.0);

                if ($account <= 0 || ($debit <= 0.0 && $credit <= 0.0)) {
                    continue;
                }

                $item = new JournalItem(
                    id: null,
                    organizationId: $orgId,
                    journalId: $journalId,
                    account: $account,
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    debit: $debit,
                    credit: $credit,
                    createdBy: $userId,
                );
                $this->journalRepo->saveItem($item);
            }

            $this->db->commit();

            return $savedJournal;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateJournal(int $id, array $data, array $itemsData, int $orgId, int $userId): Journal
    {
        $journal = $this->getJournal($id, $orgId);
        $this->validateJournalData($data, false);
        $this->validateBalance($itemsData);

        $this->db->beginTransaction();
        try {
            $journalDate = isset($data['journal_date']) ? $this->parseDate((string)$data['journal_date']) : $journal->journalDate;

            $debits = $this->sumDebits($itemsData);
            $credits = $this->sumCredits($itemsData);

            $updatedJournal = new Journal(
                id: $journal->id,
                organizationId: $journal->organizationId,
                journalStatus: (string)($data['journal_status'] ?? $journal->journalStatus),
                journalNo: $journal->journalNo,
                journalDate: $journalDate,
                referenceNo: $data['reference_no'] !== null ? (!empty($data['reference_no']) ? trim((string)$data['reference_no']) : null) : $journal->referenceNo,
                notes: trim((string)($data['notes'] ?? $journal->notes)),
                reportingMethod: (string)($data['reporting_method'] ?? $journal->reportingMethod),
                referenceType: $journal->referenceType,
                referenceId: $journal->referenceId,
                currency: (string)($data['currency'] ?? $journal->currency),
                grandSubtotal: $debits,
                grandTotal: $credits,
                warehouseId: (int)($data['warehouse_id'] ?? $journal->warehouseId),
                isActive: isset($data['publish']) ? !empty($data['publish']) : $journal->isActive,
                createdAt: $journal->createdAt,
                createdBy: $journal->createdBy,
                updatedBy: $userId,
            );

            $savedJournal = $this->journalRepo->save($updatedJournal);

            $existingItems = $this->journalRepo->findItemsByJournal($id, $orgId);
            $existingIds = array_map(fn($item) => $item->id, $existingItems);
            $incomingIds = [];

            foreach ($itemsData as $itemData) {
                $account = (int)($itemData['account'] ?? 0);
                $debit = (float)($itemData['debit'] ?? 0.0);
                $credit = (float)($itemData['credit'] ?? 0.0);

                $itemId = !empty($itemData['item_id']) ? (int)$itemData['item_id'] : null;

                if ($itemId !== null && $account <= 0 && $debit <= 0.0 && $credit <= 0.0) {
                    continue;
                }
                if ($itemId === null && ($account <= 0 || ($debit <= 0.0 && $credit <= 0.0))) {
                    continue;
                }

                if ($itemId !== null) {
                    $incomingIds[] = $itemId;
                }

                $item = new JournalItem(
                    id: $itemId,
                    organizationId: $orgId,
                    journalId: $id,
                    account: $account,
                    description: !empty($itemData['description']) ? trim((string)$itemData['description']) : null,
                    debit: $debit,
                    credit: $credit,
                    createdBy: $userId,
                );
                $this->journalRepo->saveItem($item);
            }

            $deletedIds = array_diff($existingIds, $incomingIds);
            if (!empty($deletedIds)) {
                $this->journalRepo->deleteItemsByIds($deletedIds, $id, $orgId);
            }

            $this->db->commit();

            return $savedJournal;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteJournal(int $id, int $orgId): bool
    {
        $this->getJournal($id, $orgId);

        $this->db->beginTransaction();
        try {
            $result = $this->journalRepo->delete($id, $orgId);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateJournalData(array $data, bool $requireNotes = true): void
    {
        if (empty($data['journal_date'])) {
            throw new ValidationException(['journal_date' => "Please select Journal Date."]);
        }
        if ($requireNotes && empty($data['notes'])) {
            throw new ValidationException(['notes' => "Please enter Notes."]);
        }
    }

    private function validateBalance(array $itemsData): void
    {
        $validCount = 0;
        foreach ($itemsData as $item) {
            $account = (int)($item['account'] ?? 0);
            $debit = (float)($item['debit'] ?? 0.0);
            $credit = (float)($item['credit'] ?? 0.0);

            if ($account <= 0) continue;
            if ($debit > 0 && $credit > 0) {
                throw new ValidationException(['balance' => "Line item cannot have both Debit and Credit."]);
            }
            if ($debit <= 0 && $credit <= 0) continue;
            $validCount++;
        }

        if ($validCount < 2) {
            throw new ValidationException(['balance' => "A journal entry must have at least 2 line items (one debit and one credit)."]);
        }

        $totalDebits = $this->sumDebits($itemsData);
        $totalCredits = $this->sumCredits($itemsData);

        if (abs($totalDebits - $totalCredits) > 0.01) {
            $difference = abs($totalDebits - $totalCredits);
            throw new ValidationException(['balance' => "Debits and Credits must be equal. Current difference: " . number_format($difference, 2)]);
        }
    }

    private function sumDebits(array $itemsData): float
    {
        $total = 0.0;
        foreach ($itemsData as $item) {
            $total += (float)($item['debit'] ?? 0.0);
        }
        return round($total, 2);
    }

    private function sumCredits(array $itemsData): float
    {
        $total = 0.0;
        foreach ($itemsData as $item) {
            $total += (float)($item['credit'] ?? 0.0);
        }
        return round($total, 2);
    }

    private function generateJournalNo(int $orgId): string
    {
        $prefix = 'JRN-' . date('Ymd') . '-';
        $lastNo = $this->journalRepo->getLastJournalNo($prefix, $orgId);
        if ($lastNo === null) {
            return $prefix . '0001';
        }
        $suffix = (int)substr($lastNo, -4);
        return $prefix . str_pad((string)($suffix + 1), 4, '0', STR_PAD_LEFT);
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
