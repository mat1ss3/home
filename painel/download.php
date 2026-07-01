<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/filehelpers.php';

require_login();

$path = $_GET['path'] ?? '';
$resolved = resolve_safe_path($path);

if ($resolved === false || !file_exists($resolved['abs']) || is_dir($resolved['abs'])) {
    http_response_code(404);
    die('Ficheiro não encontrado.');
}

$filename = basename($resolved['abs']);
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($resolved['abs']));
header('X-Content-Type-Options: nosniff');
readfile($resolved['abs']);
exit;
