<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/frontend/PublicAds.php';
require_once __DIR__ . '/../includes/helpers.php';

$adId = (int)($_GET['id'] ?? 0);
$fallbackUrl = url('/ads');
$forcedRedirectUrl = 'https://www.haitechnologies.com';

if ($adId <= 0) {
    header('Location: ' . $fallbackUrl);
    exit;
}

$publicAdsModel = new PublicAds($conn);
$ad = $publicAdsModel->getAdById($adId);

if (!$ad || empty($ad['is_active'])) {
    header('Location: ' . $fallbackUrl);
    exit;
}

$publicAdsModel->incrementClick($adId);
header('Location: ' . $forcedRedirectUrl);
exit;