<?php
require_once __DIR__ . '/../config.php';

/**
 * Resolve um caminho relativo (vindo do utilizador) para um caminho absoluto
 * seguro dentro de FILES_BASE_DIR. Bloqueia qualquer tentativa de sair da pasta
 * (ex: "../../etc/passwd"). Devolve false se o caminho for inválido.
 */
function resolve_safe_path(string $relative) {
    $base = realpath(FILES_BASE_DIR);
    if ($base === false) {
        @mkdir(FILES_BASE_DIR, 0755, true);
        $base = realpath(FILES_BASE_DIR);
    }

    $relative = str_replace('\\', '/', $relative);
    $parts = array_filter(explode('/', $relative), function ($p) {
        return $p !== '' && $p !== '.' && $p !== '..';
    });
    $clean = implode('/', $parts);

    $target = $clean === '' ? $base : $base . '/' . $clean;

    // Se já existe, confirmamos com realpath que continua dentro da base.
    $resolved = realpath($target);
    if ($resolved !== false) {
        if (strpos($resolved, $base) !== 0) return false;
        return ['abs' => $resolved, 'rel' => $clean];
    }

    // Se ainda não existe (ex: novo ficheiro a criar), validamos a pasta-mãe.
    $parentAbs = realpath(dirname($target));
    if ($parentAbs === false || strpos($parentAbs, $base) !== 0) return false;

    return ['abs' => $target, 'rel' => $clean];
}

function human_filesize(int $bytes): string {
    $units = ['B','KB','MB','GB'];
    $i = 0;
    $size = $bytes;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 1) . ' ' . $units[$i];
}

function is_extension_allowed(string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS, true);
}

function list_directory(string $absPath): array {
    $items = [];
    foreach (scandir($absPath) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        if ($entry === '.htaccess' || $entry === 'index.html') continue;
        $full = $absPath . '/' . $entry;
        $items[] = [
            'name' => $entry,
            'is_dir' => is_dir($full),
            'size' => is_dir($full) ? null : filesize($full),
            'modified' => filemtime($full),
        ];
    }
    usort($items, function ($a, $b) {
        if ($a['is_dir'] !== $b['is_dir']) return $a['is_dir'] ? -1 : 1;
        return strcasecmp($a['name'], $b['name']);
    });
    return $items;
}
