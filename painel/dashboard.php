<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/filehelpers.php';

require_login();

$currentPath = $_GET['path'] ?? '';
$resolved = resolve_safe_path($currentPath);
if ($resolved === false || !is_dir($resolved['abs'])) {
    $currentPath = '';
    $resolved = resolve_safe_path('');
}
$items = list_directory($resolved['abs']);

$statusMessages = [
    'upload_ok' => 'Ficheiro enviado com sucesso.',
    'ficheiro_criado' => 'Ficheiro criado com sucesso.',
    'pasta_criada' => 'Pasta criada com sucesso.',
    'apagado' => 'Item apagado com sucesso.',
    'renomeado' => 'Item renomeado com sucesso.',
    'erro_upload' => 'Erro ao enviar o ficheiro.',
    'ficheiro_grande' => 'O ficheiro excede o tamanho máximo permitido.',
    'extensao_nao_permitida' => 'Tipo de ficheiro não permitido.',
    'pasta_existe' => 'Já existe uma pasta ou ficheiro com esse nome.',
    'pasta_nao_vazia' => 'Só é possível apagar pastas vazias.',
    'nome_invalido' => 'Nome inválido.',
    'nome_ja_existe' => 'Já existe um item com esse nome.',
    'nao_encontrado' => 'Item não encontrado.',
    'caminho_invalido' => 'Caminho inválido.',
];
$status = $_GET['status'] ?? '';

// breadcrumb
$crumbs = [];
if ($currentPath !== '') {
    $accum = '';
    foreach (explode('/', $currentPath) as $part) {
        $accum = $accum === '' ? $part : $accum . '/' . $part;
        $crumbs[] = ['name' => $part, 'path' => $accum];
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel de Controlo — Ficheiros</title>
<style>
  :root{ --bg:#0F1830; --panel:#182543; --panel2:#1E2C4F; --ink:#EEF2F5; --muted:#8C9AC0;
         --accent:#FF6B1E; --amber:#FFC857; --border:#2A3A63; }
  *{box-sizing:border-box;}
  body{background:var(--bg);color:var(--ink);font-family:system-ui,-apple-system,sans-serif;margin:0;}
  header{background:var(--panel);border-bottom:1px solid var(--border);padding:16px 28px;display:flex;justify-content:space-between;align-items:center;}
  header .logo{font-weight:600;display:flex;align-items:center;gap:8px;}
  header .logo .bolt{color:var(--accent);}
  header .user{font-size:13px;color:var(--muted);display:flex;align-items:center;gap:16px;}
  header a.logout{color:var(--muted);text-decoration:none;font-size:13px;border:1px solid var(--border);padding:8px 14px;border-radius:4px;}
  header a.logout:hover{color:var(--ink);border-color:var(--accent);}
  main{max-width:1000px;margin:0 auto;padding:32px 28px;}
  h1{font-size:20px;margin:0 0 6px;}
  .sub{color:var(--muted);font-size:14px;margin-bottom:24px;}

  .breadcrumb{font-size:13px;color:var(--muted);margin-bottom:18px;display:flex;flex-wrap:wrap;gap:4px;align-items:center;}
  .breadcrumb a{color:var(--muted);text-decoration:none;}
  .breadcrumb a:hover{color:var(--amber);}
  .breadcrumb .sep{color:var(--border);}

  .toolbar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
  .toolbar form{display:flex;gap:8px;align-items:center;}
  .toolbar input[type=text]{padding:10px 12px;border-radius:4px;border:1px solid var(--border);background:var(--panel2);color:var(--ink);font-size:13px;}
  .btn{padding:10px 16px;border-radius:4px;border:none;font-size:13px;cursor:pointer;font-weight:500;}
  .btn-accent{background:var(--accent);color:#fff;}
  .btn-outline{background:transparent;color:var(--ink);border:1px solid var(--border);}
  .btn-outline:hover{border-color:var(--accent);}
  label.upload-label{padding:10px 16px;border-radius:4px;border:1px solid var(--border);font-size:13px;cursor:pointer;}
  label.upload-label:hover{border-color:var(--accent);}
  input[type=file]{display:none;}

  .status{padding:12px 16px;border-radius:4px;margin-bottom:18px;font-size:13.5px;background:#1f4a2e;color:#b4ffcf;}
  .status.err{background:#4a1f2b;color:#ffb4c1;}

  .new-file-panel{display:none;background:var(--panel);border:1px solid var(--border);border-radius:6px;padding:18px;margin-bottom:20px;}
  .new-file-panel.open{display:block;}
  .new-file-panel .nf-row{display:flex;gap:8px;margin-bottom:10px;}
  .new-file-panel input[type=text]{flex:1;padding:10px 12px;border-radius:4px;border:1px solid var(--border);background:var(--panel2);color:var(--ink);font-size:13px;}
  .new-file-panel textarea{width:100%;padding:12px;border-radius:4px;border:1px solid var(--border);background:var(--panel2);color:var(--ink);font-size:13px;font-family:ui-monospace,monospace;resize:vertical;}
  .new-file-panel .nf-hint{color:var(--muted);font-size:12px;margin-top:8px;}

  table{width:100%;border-collapse:collapse;background:var(--panel);border-radius:6px;overflow:hidden;}
  th{text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:0.04em;color:var(--muted);padding:12px 16px;border-bottom:1px solid var(--border);}
  td{padding:12px 16px;border-bottom:1px solid var(--border);font-size:14px;vertical-align:middle;}
  tr:last-child td{border-bottom:none;}
  .name a{color:var(--ink);text-decoration:none;display:flex;align-items:center;gap:8px;}
  .name a:hover{color:var(--amber);}
  .icon{opacity:0.8;}
  .meta{color:var(--muted);font-size:12.5px;}
  .actions{display:flex;gap:10px;justify-content:flex-end;}
  .actions a, .actions button{font-size:12.5px;color:var(--muted);background:none;border:none;cursor:pointer;text-decoration:none;padding:0;}
  .actions a:hover, .actions button:hover{color:var(--accent);}
  .empty{padding:40px;text-align:center;color:var(--muted);font-size:14px;}
</style>
</head>
<body>

<header>
  <div class="logo"><span class="bolt">⚡</span> Painel de Controlo</div>
  <div class="user">
    Sessão: <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></strong>
    <a class="logout" href="logout.php">Sair</a>
  </div>
</header>

<main>
  <h1>Gestor de Ficheiros</h1>
  <p class="sub">Ficheiros guardados no alojamento, dentro da pasta protegida do painel.</p>

  <?php if ($status && isset($statusMessages[$status])): ?>
    <div class="status <?= in_array($status, ['erro_upload','ficheiro_grande','extensao_nao_permitida','pasta_existe','pasta_nao_vazia','nome_invalido','nome_ja_existe','nao_encontrado','caminho_invalido']) ? 'err' : '' ?>">
      <?= htmlspecialchars($statusMessages[$status]) ?>
    </div>
  <?php endif; ?>

  <div class="breadcrumb">
    <a href="dashboard.php">Raiz</a>
    <?php foreach ($crumbs as $c): ?>
      <span class="sep">/</span>
      <a href="dashboard.php?path=<?= urlencode($c['path']) ?>"><?= htmlspecialchars($c['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="toolbar">
    <form method="post" action="file_action.php" style="display:flex;gap:8px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_folder">
      <input type="hidden" name="path" value="<?= htmlspecialchars($currentPath) ?>">
      <input type="text" name="folder_name" placeholder="Nome da nova pasta" required>
      <button class="btn btn-outline" type="submit">+ Pasta</button>
    </form>

    <button type="button" class="btn btn-outline" onclick="document.getElementById('new-file-panel').classList.toggle('open')">+ Ficheiro</button>

    <form method="post" action="file_action.php" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="upload">
      <input type="hidden" name="path" value="<?= htmlspecialchars($currentPath) ?>">
      <label class="upload-label">
        Escolher ficheiro
        <input type="file" name="file" onchange="this.form.submit()" required>
      </label>
    </form>
  </div>

  <div id="new-file-panel" class="new-file-panel">
    <form method="post" action="file_action.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_file">
      <input type="hidden" name="path" value="<?= htmlspecialchars($currentPath) ?>">
      <div class="nf-row">
        <input type="text" name="file_name" placeholder="nome-do-ficheiro.txt" required>
        <button class="btn btn-accent" type="submit">Criar ficheiro</button>
      </div>
      <textarea name="file_content" placeholder="Conteúdo do ficheiro (opcional)" rows="8"></textarea>
      <p class="nf-hint">Extensões permitidas: <?= htmlspecialchars(implode(', ', ALLOWED_EXTENSIONS)) ?></p>
    </form>
  </div>

  <table>
    <thead>
      <tr><th>Nome</th><th>Tamanho</th><th>Modificado</th><th></th></tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="4" class="empty">Esta pasta está vazia.</td></tr>
      <?php endif; ?>
      <?php foreach ($items as $item):
          $itemPath = ($currentPath !== '' ? $currentPath . '/' : '') . $item['name'];
      ?>
      <tr>
        <td class="name">
          <?php if ($item['is_dir']): ?>
            <a href="dashboard.php?path=<?= urlencode($itemPath) ?>"><span class="icon">📁</span> <?= htmlspecialchars($item['name']) ?></a>
          <?php else: ?>
            <a href="download.php?path=<?= urlencode($itemPath) ?>"><span class="icon">📄</span> <?= htmlspecialchars($item['name']) ?></a>
          <?php endif; ?>
        </td>
        <td class="meta"><?= $item['is_dir'] ? '—' : human_filesize($item['size']) ?></td>
        <td class="meta"><?= date('d/m/Y H:i', $item['modified']) ?></td>
        <td>
          <div class="actions">
            <form method="post" action="file_action.php" onsubmit="return renamePrompt(this)">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="rename">
              <input type="hidden" name="path" value="<?= htmlspecialchars($currentPath) ?>">
              <input type="hidden" name="target" value="<?= htmlspecialchars($item['name']) ?>">
              <input type="hidden" name="new_name" class="new-name-field">
              <button type="submit">Renomear</button>
            </form>
            <form method="post" action="file_action.php" onsubmit="return confirm('Apagar &quot;<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>&quot;? Esta ação não pode ser desfeita.')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="path" value="<?= htmlspecialchars($currentPath) ?>">
              <input type="hidden" name="target" value="<?= htmlspecialchars($item['name']) ?>">
              <button type="submit">Apagar</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

<script>
function renamePrompt(form) {
  const current = form.querySelector('input[name=target]').value;
  const novo = prompt('Novo nome:', current);
  if (!novo || novo.trim() === '') return false;
  form.querySelector('.new-name-field').value = novo.trim();
  return true;
}
</script>
</body>
</html>
