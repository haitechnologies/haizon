<?php
/**
 * Accounting Configuration
 *
 * Provides the AccountingJournalManager class and related accounting utilities.
 * Required by payments_received.php and payments_made.php.
 */

use App\Core\DB;

// AccountingJournalManager class - handles journal entry creation
if (!class_exists('AccountingJournalManager')) {
    class AccountingJournalManager
    {
        private $mysqli;

        public function __construct($mysqli)
        {
            $this->mysqli = $mysqli;
        }

        /**
         * Create a journal entry with line items
         *
         * @param array $journalData  Header data: reference_type, reference_id, reference_no, journal_date, description, currency, grand_total, reporting_method
         * @param array $entries      Line items: [{account, amount, type}]
         * @return int|false          Journal ID on success, false on failure
         */
        public function createJournalEntry(array $journalData, array $entries)
        {
            $journalTable     = DB::JOURNALS;
            $journalItemTable = DB::JOURNAL_ITEMS;

            $refType   = $this->mysqli->real_escape_string($journalData['reference_type'] ?? '');
            $refId     = (int)($journalData['reference_id'] ?? 0);
            $refNo     = $this->mysqli->real_escape_string($journalData['reference_no'] ?? '');
            $jDate     = $this->mysqli->real_escape_string($journalData['journal_date'] ?? date('Y-m-d'));
            $desc      = $this->mysqli->real_escape_string($journalData['description'] ?? '');
            $currency  = $this->mysqli->real_escape_string($journalData['currency'] ?? 'AED');
            $total     = (float)($journalData['grand_total'] ?? 0);
            $method    = $this->mysqli->real_escape_string($journalData['reporting_method'] ?? 'accrual');

            $sql = "INSERT INTO `{$journalTable}` 
                    (reference_type, reference_id, reference_no, journal_date, description, currency, grand_total, reporting_method)
                    VALUES ('{$refType}', {$refId}, '{$refNo}', '{$jDate}', '{$desc}', '{$currency}', {$total}, '{$method}')";

            if (!$this->mysqli->query($sql)) {
                error_log('AccountingJournalManager::createJournalEntry failed: ' . $this->mysqli->error);
                return false;
            }

            $journalId = $this->mysqli->insert_id;

            foreach ($entries as $entry) {
                $account = (int)($entry['account'] ?? 0);
                $amount  = (float)($entry['amount'] ?? 0);
                $type    = strtolower($entry['type'] ?? 'debit');
                $debit   = ($type === 'debit') ? $amount : 0.00;
                $credit  = ($type === 'credit') ? $amount : 0.00;

                $itemSql = "INSERT INTO `{$journalItemTable}` (journal_id, account, debit, credit) 
                            VALUES ({$journalId}, {$account}, {$debit}, {$credit})";
                $this->mysqli->query($itemSql);
            }

            return $journalId;
        }
    }
}
