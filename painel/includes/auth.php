<?php
require_once __DIR__ . '/../config.php';

function start_secure_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name(SESSION_NAME);
    session_start();
}

function is_logged_in(): bool {
    start_secure_session();
    return !empty($_SESSION['admin_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string {
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES);
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

function verify_csrf(): void {
    start_secure_session();
    $sent = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        die('Pedido inválido (CSRF). Recarrega a página e tenta novamente.');
    }
}

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function too_many_attempts(PDO $pdo): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM admin_login_attempts WHERE ip_address = ? AND attempted_at > datetime('now', '-15 minutes')"
    );
    $stmt->execute([client_ip()]);
    return (int)$stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
}

function record_failed_attempt(PDO $pdo): void {
    $stmt = $pdo->prepare('INSERT INTO admin_login_attempts (ip_address) VALUES (?)');
    $stmt->execute([client_ip()]);
}
