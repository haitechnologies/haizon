<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use RuntimeException;
use Throwable;

class AccountingJournalManager
{
    /** @var mixed */
    private mixed $db;

    public function __construct(mixed $mysqli = null)
    {
        if ($mysqli instanceof Database) {
            $this->db = $mysqli;
        } else {
            try {
                $container = Container::getInstance();
                if ($container->has(Database::class)) {
                    $this->db = $container->get(Database::class);
                } else {
                    $this->db = new Database();
                }
            } catch (Throwable $e) {
                $this->db = new Database();
            }
        }
    }

    /**
     * Create one journal and its line items.
     *
     * @param array $journalData
     * @param array $journalEntries
     * @return array{success:bool,journal_id:int,message:string}
     */
    public function createJournalEntry(array $journalData, array $journalEntries): array
    {
        if (empty($journalEntries)) {
            return [
                'success' => false,
                'journal_id' => 0,
                'message' => 'No journal entries provided.'
            ];
        }

        $totals = $this->calculateTotals($journalEntries);
        if (abs($totals['debit'] - $totals['credit']) > 0.01) {
            return [
                'success' => false,
                'journal_id' => 0,
                'message' => 'Journal is not balanced.'
            ];
        }

        $journalId = 0;

        try {
            $this->db->beginTransaction();

            $journalDate = isset($journalData['journal_date']) ? (string)$journalData['journal_date'] : date('Y-m-d');
            $referenceType = isset($journalData['reference_type']) ? (string)$journalData['reference_type'] : '';
            $referenceId = isset($journalData['reference_id']) ? (int)$journalData['reference_id'] : 0;
            $referenceNo = isset($journalData['reference_no']) ? (string)$journalData['reference_no'] : '';
            $notes = isset($journalData['description']) ? (string)$journalData['description'] : '';
            $currency = isset($journalData['currency']) ? (string)$journalData['currency'] : 'AED';
            $grandTotal = isset($journalData['grand_total']) ? (float)$journalData['grand_total'] : (float)$totals['debit'];
            $journalStatus = isset($journalData['journal_status']) ? (string)$journalData['journal_status'] : 'posted';
            $reportingMethod = isset($journalData['reporting_method']) ? (string)$journalData['reporting_method'] : 'accrual';
            $createdBy = isset($_SESSION[$GLOBALS['project_pre']]['DASHBOARD']['id'])
                ? (int)$_SESSION[$GLOBALS['project_pre']]['DASHBOARD']['id']
                : 0;

            $journalNo = $this->generateJournalNo();

            $insertJournalSql = "INSERT INTO `" . DB::JOURNALS . "` (
                journal_status, journal_no, reference_no, journal_date,
                reference_type, reference_id, notes, reporting_method,
                currency, grand_subtotal, grand_total, is_active, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";

            $journalIdStr = $this->db->insert($insertJournalSql, [
                $journalStatus,
                $journalNo,
                $referenceNo,
                $journalDate,
                $referenceType,
                $referenceId,
                $notes,
                $reportingMethod,
                $currency,
                $totals['debit'],
                $grandTotal,
                $createdBy
            ]);

            $journalId = (int)$journalIdStr;
            if ($journalId <= 0) {
                throw new RuntimeException('Failed to retrieve insert ID for journal entry.');
            }

            $insertItemSql = "INSERT INTO `" . DB::JOURNAL_ITEMS . "` (journal_id, account, description, debit, credit) VALUES (?, ?, ?, ?, ?)";

            foreach ($journalEntries as $entry) {
                $accountId = isset($entry['account']) ? (int)$entry['account'] : 0;
                if ($accountId <= 0) {
                    continue;
                }

                $type = strtolower((string)($entry['type'] ?? ''));
                $amount = (float)($entry['amount'] ?? 0);
                $description = (string)($entry['description'] ?? '');
                $debit = $type === 'debit' ? $amount : 0.00;
                $credit = $type === 'credit' ? $amount : 0.00;

                $this->db->execute($insertItemSql, [$journalId, $accountId, $description, $debit, $credit]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'journal_id' => $journalId,
                'message' => 'Journal entry created successfully.'
            ];
        } catch (Throwable $e) {
            try {
                $this->db->rollBack();
            } catch (Throwable $rollbackEx) {
                // Ignore rollback exceptions
            }

            return [
                'success' => false,
                'journal_id' => $journalId,
                'message' => $e->getMessage()
            ];
        }
    }

    private function calculateTotals(array $entries): array
    {
        $debit = 0.00;
        $credit = 0.00;

        foreach ($entries as $entry) {
            $amount = (float)($entry['amount'] ?? 0);
            $type = strtolower((string)($entry['type'] ?? ''));

            if ($type === 'debit') {
                $debit += $amount;
            } elseif ($type === 'credit') {
                $credit += $amount;
            }
        }

        return [
            'debit' => round($debit, 2),
            'credit' => round($credit, 2)
        ];
    }

    private function generateJournalNo(): string
    {
        $prefix = 'JRN-' . date('Ymd') . '-';

        $sql = "SELECT journal_no FROM `" . DB::JOURNALS . "` WHERE journal_no LIKE ? ORDER BY id DESC LIMIT 1";
        $like = $prefix . '%';

        try {
            $row = $this->db->fetchOne($sql, [$like]);
            if (!$row || empty($row['journal_no'])) {
                return $prefix . '0001';
            }

            $last = (string)$row['journal_no'];
            $suffix = (int)substr($last, -4);

            return $prefix . str_pad((string)($suffix + 1), 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return $prefix . '0001';
        }
    }
}
