<?php
session_start();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') === ADMIN_PASSWORD) {
        $_SESSION['dd_admin'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Contraseña incorrecta.';
}

if (!empty($_SESSION['dd_admin'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DigitalDuck Admin — Acceso</title>
<style>
:root{--purple:#4c1d95;--purple-mid:#5b21b6;--yellow:#facc15;--gray-100:#f9f4f2;--gray-500:#6b7280;--gray-700:#374151;--radius:10px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:var(--purple);color:var(--gray-100);min-height:100vh;display:flex;align-items:center;justify-content:center}
.box{background:var(--purple-mid);border:1px solid #6d28d9;border-radius:var(--radius);padding:2rem;width:100%;max-width:340px}
.box h1{font-size:1.1rem;color:var(--yellow);margin-bottom:.25rem}
.box p{font-size:.8rem;color:#d1c4f0;margin-bottom:1.5rem}
label{font-size:.75rem;color:#d1c4f0;display:block;margin-bottom:.3rem}
input{width:100%;background:var(--purple);border:1px solid #6d28d9;color:var(--gray-100);padding:.5rem .75rem;border-radius:6px;font-size:.9rem;margin-bottom:1rem}
input:focus{outline:none;border-color:var(--yellow)}
button{width:100%;background:var(--yellow);color:var(--purple);font-weight:600;border:none;padding:.6rem;border-radius:6px;font-size:.9rem;cursor:pointer}
button:hover{opacity:.9}
.error{background:rgba(255,80,80,.12);border:1px solid rgba(255,80,80,.3);color:#f87171;padding:.5rem .75rem;border-radius:6px;font-size:.8rem;margin-bottom:1rem}
.back{display:block;text-align:center;margin-top:1rem;font-size:.78rem;color:#d1c4f0;text-decoration:none}
.back:hover{color:var(--yellow)}
</style>
</head>
<body>
<div class="box">
  <h1>🦆 DigitalDuck Admin</h1>
  <p>Panel de gestión de prácticos y láminas</p>
  <?php if (!empty($error)): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" autofocus required>
    <button type="submit">Ingresar</button>
  </form>
  <a class="back" href="../index.html">← Volver al portal</a>
</div>
</body>
</html>
