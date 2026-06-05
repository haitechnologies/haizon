<?php

class AccountingJournalManager
{
    /** @var mysqli */
    private $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
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
            $this->mysqli->begin_transaction();

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
                currency, grand_subtotal, grand_total, publish, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";

            $journalStmt = $this->mysqli->prepare($insertJournalSql);
            if (!$journalStmt) {
                throw new RuntimeException('Unable to prepare journal insert.');
            }

            $journalStmt->bind_param(
                'sssssisssddi',
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
            );

            if (!$journalStmt->execute()) {
                $journalStmt->close();
                throw new RuntimeException('Failed to create journal entry.');
            }

            $journalId = (int)$this->mysqli->insert_id;
            $journalStmt->close();

            $insertItemSql = "INSERT INTO `" . DB::JOURNAL_ITEMS . "` (journal_id, account, description, debit, credit) VALUES (?, ?, ?, ?, ?)";
            $itemStmt = $this->mysqli->prepare($insertItemSql);
            if (!$itemStmt) {
                throw new RuntimeException('Unable to prepare journal item insert.');
            }

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

                $itemStmt->bind_param('iisdd', $journalId, $accountId, $description, $debit, $credit);
                if (!$itemStmt->execute()) {
                    $itemStmt->close();
                    throw new RuntimeException('Failed to insert journal item.');
                }
            }

            $itemStmt->close();
            $this->mysqli->commit();

            return [
                'success' => true,
                'journal_id' => $journalId,
                'message' => 'Journal entry created successfully.'
            ];
        } catch (Throwable $e) {
            $this->mysqli->rollback();

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
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return $prefix . '0001';
        }

        $like = $prefix . '%';
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row || empty($row['journal_no'])) {
            return $prefix . '0001';
        }

        $last = (string)$row['journal_no'];
        $suffix = (int)substr($last, -4);

        return $prefix . str_pad((string)($suffix + 1), 4, '0', STR_PAD_LEFT);
    }
}
