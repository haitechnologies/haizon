<?php
header("Content-Type: image/png");
require "../vendor/autoload.php";

use Endroid\QrCode\QrCode;

if (!isset($_GET['code']) || trim((string)$_GET['code']) === '') {
	if (!headers_sent()) {
		http_response_code(400);
		header('Content-Type: text/plain; charset=utf-8');
	}
	echo 'Missing code parameter.';
	exit;
}

$code = trim((string)$_GET['code']);
$qrCode = new QrCode($code);
echo $qrCode->writeString();

// file_put_contents('dummy1.png', $qrCode->writeString());
$safeFileName = preg_replace('/[^A-Za-z0-9_-]/', '_', $code);
if ($safeFileName !== '') {
	file_put_contents($safeFileName . '.png', $qrCode->writeString());
}
