<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/filehelpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}
verify_csrf();

$action = $_POST['action'] ?? '';
$currentPath = $_POST['path'] ?? '';

$currentResolved = resolve_safe_path($currentPath);
if ($currentResolved === false) {
    header('Location: dashboard.php?error=caminho_invalido');
    exit;
}

function redirect_back(string $path, string $status): void {
    header('Location: dashboard.php?path=' . urlencode($path) . '&status=' . urlencode($status));
    exit;
}

switch ($action) {

    case 'create_folder':
        $name = trim($_POST['folder_name'] ?? '');
        $name = preg_replace('/[^A-Za-z0-9 _\-áéíóúãõâêôçÁÉÍÓÚÃÕÂÊÔÇ]/u', '', $name);
        if ($name === '') redirect_back($currentPath, 'nome_invalido');

        $newPath = ($currentPath !== '' ? $currentPath . '/' : '') . $name;
        $resolved = resolve_safe_path($newPath);
        if ($resolved === false || is_dir($resolved['abs']) || file_exists($resolved['abs'])) {
            redirect_back($currentPath, 'pasta_existe');
        }
        mkdir($resolved['abs'], 0755);
        redirect_back($currentPath, 'pasta_criada');
        break;

    case 'create_file':
        $name = trim($_POST['file_name'] ?? '');
        $content = $_POST['file_content'] ?? '';
        $name = preg_replace('/[^A-Za-z0-9 _.\-áéíóúãõâêôçÁÉÍÓÚÃÕÂÊÔÇ]/u', '', $name);

        if ($name === '') redirect_back($currentPath, 'nome_invalido');
        if (!is_extension_allowed($name)) redirect_back($currentPath, 'extensao_nao_permitida');
        if (strlen($content) > MAX_UPLOAD_SIZE) redirect_back($currentPath, 'ficheiro_grande');

        $newPath = ($currentPath !== '' ? $currentPath . '/' : '') . $name;
        $resolved = resolve_safe_path($newPath);
        if ($resolved === false || file_exists($resolved['abs'])) {
            redirect_back($currentPath, 'nome_ja_existe');
        }
        file_put_contents($resolved['abs'], $content);
        redirect_back($currentPath, 'ficheiro_criado');
        break;

    case 'upload':
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            redirect_back($currentPath, 'erro_upload');
        }
        $file = $_FILES['file'];

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            redirect_back($currentPath, 'ficheiro_grande');
        }
        if (!is_extension_allowed($file['name'])) {
            redirect_back($currentPath, 'extensao_nao_permitida');
        }

        $safeName = preg_replace('/[^A-Za-z0-9 _.\-áéíóúãõâêôçÁÉÍÓÚÃÕÂÊÔÇ]/u', '', basename($file['name']));
        $destPath = ($currentPath !== '' ? $currentPath . '/' : '') . $safeName;
        $resolved = resolve_safe_path($destPath);
        if ($resolved === false) redirect_back($currentPath, 'erro_upload');

        if (!move_uploaded_file($file['tmp_name'], $resolved['abs'])) {
            redirect_back($currentPath, 'erro_upload');
        }
        redirect_back($currentPath, 'upload_ok');
        break;

    case 'delete':
        $target = $_POST['target'] ?? '';
        $targetPath = ($currentPath !== '' ? $currentPath . '/' : '') . $target;
        $resolved = resolve_safe_path($targetPath);
        if ($resolved === false || !file_exists($resolved['abs'])) {
            redirect_back($currentPath, 'nao_encontrado');
        }
        if (is_dir($resolved['abs'])) {
            $isEmpty = count(scandir($resolved['abs'])) <= 2;
            if (!$isEmpty) redirect_back($currentPath, 'pasta_nao_vazia');
            rmdir($resolved['abs']);
        } else {
            unlink($resolved['abs']);
        }
        redirect_back($currentPath, 'apagado');
        break;

    case 'rename':
        $target = $_POST['target'] ?? '';
        $newName = trim($_POST['new_name'] ?? '');
        $newName = preg_replace('/[^A-Za-z0-9 _.\-áéíóúãõâêôçÁÉÍÓÚÃÕÂÊÔÇ]/u', '', $newName);
        if ($newName === '') redirect_back($currentPath, 'nome_invalido');

        $oldResolved = resolve_safe_path(($currentPath !== '' ? $currentPath . '/' : '') . $target);
        $newResolved = resolve_safe_path(($currentPath !== '' ? $currentPath . '/' : '') . $newName);

        if ($oldResolved === false || $newResolved === false || !file_exists($oldResolved['abs'])) {
            redirect_back($currentPath, 'nao_encontrado');
        }
        if (file_exists($newResolved['abs'])) {
            redirect_back($currentPath, 'nome_ja_existe');
        }
        rename($oldResolved['abs'], $newResolved['abs']);
        redirect_back($currentPath, 'renomeado');
        break;

    default:
        redirect_back($currentPath, 'acao_invalida');
}
