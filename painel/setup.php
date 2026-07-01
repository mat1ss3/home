<?php
require_once __DIR__ . '/includes/db.php';

$pdo = get_db();
$count = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

$error = '';
$success = false;

// Por segurança, este ficheiro só funciona enquanto não existir NENHUM admin.
// Depois de criares a tua conta, apaga este ficheiro do servidor.
if ($count > 0) {
    $error = 'Já existe uma conta de administrador. Este ficheiro está bloqueado. Apaga o setup.php do servidor.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($username) < 3) {
        $error = 'O nome de utilizador deve ter pelo menos 3 caracteres.';
    } elseif (strlen($password) < 10) {
        $error = 'A password deve ter pelo menos 10 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'As passwords não coincidem.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, $hash]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configuração inicial — Painel</title>
<style>
  body{background:#14213D;color:#EEF2F5;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
  .card{background:#1B2A4E;padding:40px;border-radius:8px;max-width:420px;width:90%;}
  h1{font-size:20px;margin-bottom:8px;}
  p.sub{color:#9AA7C7;font-size:14px;margin-bottom:24px;}
  label{display:block;font-size:13px;margin-bottom:6px;color:#9AA7C7;}
  input{width:100%;padding:12px;margin-bottom:16px;border-radius:4px;border:1px solid #33456e;background:#101a33;color:#fff;font-size:14px;}
  button{width:100%;padding:13px;background:#FF6B1E;color:#fff;border:none;border-radius:4px;font-size:14px;cursor:pointer;}
  .error{background:#4a1f2b;color:#ffb4c1;padding:12px;border-radius:4px;margin-bottom:18px;font-size:13.5px;}
  .success{background:#1f4a2e;color:#b4ffcf;padding:16px;border-radius:4px;font-size:14px;line-height:1.6;}
  a{color:#FFC857;}
</style>
</head>
<body>
<div class="card">
  <h1>Configuração inicial do painel</h1>
  <p class="sub">Cria a tua conta de administrador. Isto só pode ser feito uma vez.</p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="success">
      Conta criada com sucesso!<br><br>
      <strong>Importante:</strong> apaga agora o ficheiro <code>setup.php</code> do servidor
      (via File Manager do cPanel), por segurança.<br><br>
      <a href="login.php">Ir para o login →</a>
    </div>
  <?php else: ?>
    <form method="post">
      <label>Nome de utilizador</label>
      <input type="text" name="username" required minlength="3" autofocus>
      <label>Password (mínimo 10 caracteres)</label>
      <input type="password" name="password" required minlength="10">
      <label>Confirmar password</label>
      <input type="password" name="confirm" required minlength="10">
      <button type="submit">Criar conta</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
