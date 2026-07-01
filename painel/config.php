<?php
/**
 * CONFIGURAÇÃO DO PAINEL
 * Não precisas de criar nenhuma base de dados no cPanel — este painel
 * usa SQLite, que é criado automaticamente como um ficheiro local.
 */

// --- Base de Dados ---
// Usa SQLite: é apenas um ficheiro dentro desta pasta, não precisas de criar
// nada no cPanel. O ficheiro é criado automaticamente na primeira utilização.
define('DB_FILE', __DIR__ . '/data/panel.sqlite');

// --- Gestor de Ficheiros ---
// Pasta onde os ficheiros geridos pelo painel ficam guardados.
// Por definição, é a subpasta "files" dentro desta mesma pasta do painel.
define('FILES_BASE_DIR', __DIR__ . '/files');

// Tamanho máximo permitido por upload (bytes). 20MB por definição.
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024);

// Extensões de ficheiro permitidas no upload (lista branca, por segurança).
define('ALLOWED_EXTENSIONS', [
    'jpg','jpeg','png','gif','webp','svg',
    'pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv',
    'zip','mp4','mp3','json'
]);

// --- Sessão ---
// Nome único da cookie de sessão (evita conflitos com outros sites no mesmo alojamento)
define('SESSION_NAME', 'painel_jmf_session');

// Máximo de tentativas de login falhadas permitidas por IP em 15 minutos
define('MAX_LOGIN_ATTEMPTS', 6);
