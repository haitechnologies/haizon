<?php

use App\Core\DB;
/**
 * Minimal Admin Header for CSV Export
 * 
 * Loads only essential components without full UI:
 * - Session management
 * - Database connection
 * - Authentication check
 * - Permission functions
 * - No HTML output
 */

// Load bootstrap (security headers, session, database)
require_once __DIR__ . '/../bootstrap.php';

// Check if user is logged in (redirect if not)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Set global variables for compatibility
$session_user_id = $_SESSION['user_id'];
$session_role_id = $_SESSION['role_id'] ?? null;

// Already loaded by bootstrap.php:
// - $mysqli (database connection)
// - granted() / granted_() functions
// - getModuleIdBySlug() function
// - All DB:: constants from classes/DB.php
