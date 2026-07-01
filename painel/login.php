<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

start_secure_session();

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pdo = get_db();

    if (too_many_attempts($pdo)) {
        $error = 'Demasiadas tentativas falhadas. Tenta novamente dentro de 15 minutos.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $username;

            $upd = $pdo->prepare('UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?');
            $upd->execute([$user['id']]);

            header('Location: dashboard.php');
            exit;
        } else {
            record_failed_attempt($pdo);
            $error = 'Credenciais inválidas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Painel de Controlo</title>
<style>
  body{background:#14213D;color:#EEF2F5;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
  .card{background:#1B2A4E;padding:40px;border-radius:8px;max-width:380px;width:90%;}
  h1{font-size:20px;margin-bottom:24px;display:flex;align-items:center;gap:8px;}
  .bolt{color:#FF6B1E;}
  label{display:block;font-size:13px;margin-bottom:6px;color:#9AA7C7;}
  input{width:100%;box-sizing:border-box;padding:12px;margin-bottom:16px;border-radius:4px;border:1px solid #33456e;background:#101a33;color:#fff;font-size:14px;}
  button{width:100%;padding:13px;background:#FF6B1E;color:#fff;border:none;border-radius:4px;font-size:14px;cursor:pointer;}
  .error{background:#4a1f2b;color:#ffb4c1;padding:12px;border-radius:4px;margin-bottom:18px;font-size:13.5px;}
</style>
</head>
<body>
<div class="card">
  <h1><span class="bolt">⚡</span> Painel de Controlo</h1>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Utilizador</label>
    <input type="text" name="username" required autofocus>
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit">Entrar</button>
  </form>
</div>
</body>
</html>
