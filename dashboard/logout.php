<?php
require_once __DIR__ . '/../config/session.php';
startDashboardSession();
include('../config/globals.php');
include('../config/database.php');

	clearDashboardAuthSession($project_pre ?? null);
	session_regenerate_id(true);

	header("Location:login.php");