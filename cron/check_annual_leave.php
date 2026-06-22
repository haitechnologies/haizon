#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Annual Leave Cron Check
 *
 * CLI script run daily to check employee milestones and create
 * leave entitlements / HR todo tasks automatically.
 *
 * Usage: php cron/check_annual_leave.php
 */

// Bootstrap — minimal DB setup
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/constants.php';

$envCandidates = [
    dirname(__DIR__),
    dirname(__DIR__, 2),
    dirname(__DIR__, 3),
];

$envLoaded = false;
foreach ($envCandidates as $candidateDir) {
    if (is_file($candidateDir . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($candidateDir);
        $dotenv->safeLoad();
        $envLoaded = true;
        break;
    }
}
if (!$envLoaded) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

$appEnv = strtolower(getenv('APP_ENV') ?: '');
error_reporting(E_ALL);
ini_set('display_errors', $appEnv === 'development' ? '1' : '0');

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Dubai');

use App\Core\Database;
use App\Core\DB;

$db = new Database();

// Log helper
$log = static function (string $message): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
};

$log('Starting annual leave check...');

// Get all organizations
$orgs = $db->fetchAll("SELECT id, organization_name FROM " . DB::ORGANIZATIONS . " WHERE is_active = 1");

if (empty($orgs)) {
    $log('No active organizations found. Exiting.');
    exit(0);
}

$currentYear = (int)date('Y');

foreach ($orgs as $org) {
    $orgId = (int)$org['id'];
    $orgName = $org['organization_name'] ?? "ID {$orgId}";
    $log("Processing organization: {$orgName}");

    // --- 6-month milestones: create HR todo reminder if not already exists ---
    $sixMonthEmployees = $db->fetchAll(
        "SELECT u.id, u.full_name, u.date_of_joining
         FROM " . DB::USERS . " u
         WHERE u.organization_id = :org_id
           AND u.is_active = 1
           AND u.date_of_joining IS NOT NULL
           AND u.date_of_joining >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
           AND u.date_of_joining < DATE_SUB(CURDATE(), INTERVAL 180 DAY)
           AND u.id NOT IN (
               SELECT t.employee_id
               FROM " . DB::HR_TODO_TASKS . " t
               WHERE t.organization_id = :org_id2
                 AND t.task_type = 'annual_leave_reminder'
                 AND t.status = 'pending'
           )
         ORDER BY u.full_name ASC",
        ['org_id' => $orgId, 'org_id2' => $orgId]
    );

    foreach ($sixMonthEmployees as $emp) {
        $empId = (int)$emp['id'];
        $empName = $emp['full_name'] ?? "ID {$empId}";

        $db->insert(
            "INSERT INTO `{DB::HR_TODO_TASKS}`
             (organization_id, employee_id, task_type, description, due_date, status, created_by)
             VALUES (:org_id, :employee_id, :task_type, :description, :due_date, :status, :created_by)",
            [
                'org_id' => $orgId,
                'employee_id' => $empId,
                'task_type' => 'annual_leave_reminder',
                'description' => "{$empName} has completed 6 months (joined {$emp['date_of_joining']}). Review leave eligibility and prepare for annual entitlement.",
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'pending',
                'created_by' => 0,
            ]
        );

        $log("  Created 6-month reminder for {$empName}");
    }

    if (empty($sixMonthEmployees)) {
        $log("  No 6-month milestones found.");
    }

    // --- 12-month milestones: create annual leave entitlement + air ticket ---
    $twelveMonthEmployees = $db->fetchAll(
        "SELECT u.id, u.full_name, u.date_of_joining
         FROM " . DB::USERS . " u
         WHERE u.organization_id = :org_id
           AND u.is_active = 1
           AND u.date_of_joining IS NOT NULL
           AND u.date_of_joining <= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
           AND u.id NOT IN (
               SELECT ale.employee_id
               FROM " . DB::ANNUAL_LEAVE_ENTITLEMENTS . " ale
               WHERE ale.organization_id = :org_id2
                 AND ale.entitlement_year = :current_year
           )
         ORDER BY u.full_name ASC",
        ['org_id' => $orgId, 'org_id2' => $orgId, 'current_year' => $currentYear]
    );

    foreach ($twelveMonthEmployees as $emp) {
        $empId = (int)$emp['id'];
        $empName = $emp['full_name'] ?? "ID {$empId}";

        // Create annual leave entitlement
        $db->insert(
            "INSERT INTO `{DB::ANNUAL_LEAVE_ENTITLEMENTS}`
             (organization_id, employee_id, entitlement_year, total_leave_days, leave_availed, leave_balance, air_ticket_amount, air_ticket_availed, status, created_by)
             VALUES (:org_id, :employee_id, :year, :total_days, 0, :total_days, :ticket_amount, 0, 'active', 0)",
            [
                'org_id' => $orgId,
                'employee_id' => $empId,
                'year' => $currentYear,
                'total_days' => 30.0,
                'ticket_amount' => 1250.00,
            ]
        );

        $log("  Created annual leave entitlement for {$empName} ({$currentYear})");

        // Check if air ticket already exists
        $existingTicket = $db->fetchOne(
            "SELECT id FROM " . DB::AIR_TICKETS . "
             WHERE employee_id = :emp_id AND organization_id = :org_id AND status != 'cancelled' LIMIT 1",
            ['emp_id' => $empId, 'org_id' => $orgId]
        );

        if ($existingTicket === null) {
            $db->insert(
                "INSERT INTO `{DB::AIR_TICKETS}`
                 (organization_id, employee_id, entitlement_amount, status, eligibility_date, created_by)
                 VALUES (:org_id, :employee_id, :amount, 'pending', :eligibility_date, 0)",
                [
                    'org_id' => $orgId,
                    'employee_id' => $empId,
                    'amount' => 1250.00,
                    'eligibility_date' => date('Y-m-d'),
                ]
            );

            $log("  Created air ticket entitlement for {$empName}");
        }
    }

    if (empty($twelveMonthEmployees)) {
        $log("  No 12-month milestones found.");
    }
}

$log('Annual leave check completed successfully.');
