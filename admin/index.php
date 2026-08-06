<?php
error_reporting(0);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/db.php';
$pdo = get_db();

$practicos_count = (int) $pdo->query('SELECT COUNT(*) FROM practicos')->fetchColumn();
$laminas_count   = (int) $pdo->query('SELECT COUNT(*) FROM laminas')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DigitalDuck Admin</title>
<style>
:root{--purple:#4c1d95;--purple-mid:#5b21b6;--yellow:#facc15;--gray-100:#f9f4f2;--gray-300:#e5d9f5;--gray-500:#a78bda;--gray-700:#6d28d9;--radius:10px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:var(--purple);color:var(--gray-100);min-height:100vh}
a{color:var(--yellow);text-decoration:none}
a:hover{text-decoration:underline}
header{background:var(--purple-mid);padding:.75rem 2rem;border-bottom:1px solid var(--gray-700);display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
header h1{font-size:1.1rem;color:var(--yellow)}
.header-nav{display:flex;gap:.5rem;margin-left:auto;flex-wrap:wrap}
.header-nav a{font-size:.8rem;color:var(--yellow);padding:.3rem .75rem;border:1px solid rgba(250,204,21,.3);border-radius:6px}
.header-nav a:hover{background:rgba(250,204,21,.1);text-decoration:none}
.main{padding:2rem;max-width:900px;margin:0 auto}
.cards{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1.5rem}
.card{background:var(--purple-mid);border:1px solid var(--gray-700);border-radius:var(--radius);padding:1.5rem}
.card h2{font-size:1rem;color:var(--yellow);margin-bottom:.4rem}
.card .count{font-size:2rem;font-weight:700;margin:.5rem 0}
.card p{font-size:.8rem;color:var(--gray-300);margin-bottom:1rem}
.btn{display:inline-block;background:var(--yellow);color:var(--purple);font-weight:600;padding:.5rem 1rem;border-radius:6px;font-size:.85rem}
.btn:hover{opacity:.9;text-decoration:none}
.tools{margin-top:1.5rem;font-size:.85rem;color:var(--gray-300)}
</style>
</head>
<body>
<header>
  <h1>🦆 DigitalDuck Admin</h1>
  <nav class="header-nav">
    <a href="migrate.php">🔧 Migración DB</a>
    <a href="../index.html" target="_blank">← Ver sitio</a>
    <a href="logout.php">Cerrar sesión</a>
  </nav>
</header>
<div class="main">
  <div class="cards">
    <div class="card">
      <h2>Prácticos</h2>
      <div class="count"><?= $practicos_count ?></div>
      <p>Índice de prácticos del portal (menú y grilla de <code>practicos.html</code>).</p>
      <a class="btn" href="practicos.php">Gestionar prácticos</a>
    </div>
    <div class="card">
      <h2>Láminas</h2>
      <div class="count"><?= $laminas_count ?></div>
      <p>Catálogo clínico de láminas (<code>catalogo.html</code>), incluye casos rápidos.</p>
      <a class="btn" href="laminas.php">Gestionar láminas</a>
    </div>
  </div>
  <p class="tools">¿Prefieres seguir usando el <a href="../practico-generator.html">generador de prácticos</a> para armar el contenido y luego subirlo aquí? También puedes hacerlo directo desde este panel.</p>
</div>
</body>
</html>
