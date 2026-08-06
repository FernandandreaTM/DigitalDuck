<?php
error_reporting(0);
ob_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/db.php';
$pdo = get_db();

function safe_filename(string $name): string {
    $name = basename($name);
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
    return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_practico') {
        $id          = (int)($_POST['id'] ?? 0);
        $file        = safe_filename(trim($_POST['file'] ?? ''));
        $num         = trim($_POST['num'] ?? '');
        $name        = trim($_POST['name'] ?? '');
        $title       = trim($_POST['title'] ?? '');
        $icon        = trim($_POST['icon'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $orden       = (int)($_POST['orden'] ?? 0);

        $error = '';
        if ($file === '' || !preg_match('/\.html$/i', $file)) {
            $error = 'El nombre de archivo debe terminar en .html';
        }

        // Subida opcional del archivo .html real a la raíz del sitio
        if (!$error && isset($_FILES['html_file']) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK) {
            if (!preg_match('/\.html$/i', $_FILES['html_file']['name'])) {
                $error = 'El archivo subido debe ser .html';
            } else {
                $dest = __DIR__ . '/../' . $file;
                if (!move_uploaded_file($_FILES['html_file']['tmp_name'], $dest)) {
                    $error = 'No se pudo guardar el archivo .html en el sitio.';
                }
            }
        }

        if (!$error) {
            if ($id > 0) {
                $pdo->prepare("UPDATE practicos SET file=?,num=?,name=?,title=?,icon=?,description=?,orden=? WHERE id=?")
                    ->execute([$file,$num,$name,$title,$icon,$description,$orden,$id]);
                $msg = 'Práctico actualizado.';
            } else {
                $pdo->prepare("INSERT INTO practicos (file,num,name,title,icon,description,orden) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$file,$num,$name,$title,$icon,$description,$orden]);
                $id = (int)$pdo->lastInsertId();
                $msg = 'Práctico creado.';
            }
            header('Location: '.$_SERVER['PHP_SELF'].'?practico_id='.$id.'&msg='.urlencode($msg));
            exit;
        }
        header('Location: '.$_SERVER['PHP_SELF'].($id > 0 ? '?practico_id='.$id : '').'&error='.urlencode($error));
        exit;
    }

    if ($action === 'delete_practico') {
        $id = (int)($_POST['id'] ?? 0);
        // No se borra el archivo .html del sitio, solo la entrada del índice.
        $pdo->prepare("DELETE FROM practicos WHERE id=?")->execute([$id]);
        header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('Práctico eliminado del índice.'));
        exit;
    }
}

$practico_id  = (int)($_GET['practico_id'] ?? 0);
$msg          = $_GET['msg'] ?? '';
$error        = $_GET['error'] ?? '';
$practicos    = $pdo->query("SELECT id,file,num,name,orden FROM practicos ORDER BY orden,id")->fetchAll();
$edit         = null;

if ($practico_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM practicos WHERE id=?");
    $stmt->execute([$practico_id]);
    $edit = $stmt->fetch();
}

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DigitalDuck Admin — Prácticos</title>
<style>
:root{--purple:#4c1d95;--purple-mid:#5b21b6;--yellow:#facc15;--green:#4ade80;--red:#f87171;--gray-100:#f9f4f2;--gray-300:#e5d9f5;--gray-500:#a78bda;--gray-700:#6d28d9;--radius:10px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:var(--purple);color:var(--gray-100);min-height:100vh}
a{color:var(--yellow);text-decoration:none}
a:hover{text-decoration:underline}
header{background:var(--purple-mid);padding:.75rem 2rem;border-bottom:1px solid var(--gray-700);display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
header h1{font-size:1.1rem;color:var(--yellow)}
.header-nav{display:flex;gap:.5rem;margin-left:auto;flex-wrap:wrap}
.header-nav a{font-size:.8rem;color:var(--yellow);padding:.3rem .75rem;border:1px solid rgba(250,204,21,.3);border-radius:6px}
.header-nav a:hover{background:rgba(250,204,21,.1);text-decoration:none}
.layout{display:grid;grid-template-columns:260px 1fr;min-height:calc(100vh - 52px)}
.sidebar{background:var(--purple-mid);border-right:1px solid var(--gray-700);padding:1.25rem}
.sidebar h2{font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--gray-500);margin-bottom:.75rem}
.item{display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;border-radius:var(--radius);margin-bottom:.25rem;font-size:.875rem}
.item.active-item{background:rgba(250,204,21,.15)}
.num{font-size:.7rem;color:var(--gray-500)}
.btn-new{display:block;width:100%;text-align:center;background:var(--green);color:#0f2e1d;border:none;padding:.6rem;border-radius:var(--radius);cursor:pointer;font-size:.875rem;margin-bottom:1rem}
.main{padding:1.5rem 2rem}
.msg{background:rgba(74,222,128,.15);border:1px solid var(--green);color:var(--green);padding:.6rem 1rem;border-radius:var(--radius);margin-bottom:1rem;font-size:.875rem}
.err{background:rgba(248,113,113,.15);border:1px solid var(--red);color:var(--red);padding:.6rem 1rem;border-radius:var(--radius);margin-bottom:1rem;font-size:.875rem}
.card{background:var(--purple-mid);border:1px solid var(--gray-700);border-radius:var(--radius);padding:1.25rem;margin-bottom:1.25rem}
.card h3{font-size:.9rem;color:var(--yellow);margin-bottom:1rem;text-transform:uppercase;letter-spacing:.05em}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem}
.field{display:flex;flex-direction:column;gap:.3rem}
label{font-size:.75rem;color:var(--gray-500)}
input,textarea{background:var(--purple);border:1px solid var(--gray-700);color:var(--gray-100);padding:.45rem .65rem;border-radius:6px;font-size:.875rem;width:100%}
textarea{resize:vertical;min-height:80px}
input:focus,textarea:focus{outline:none;border-color:var(--yellow)}
.btn{padding:.45rem 1rem;border-radius:6px;border:none;cursor:pointer;font-size:.8rem}
.btn-yellow{background:var(--yellow);color:var(--purple);font-weight:600}
.btn-del{background:rgba(248,113,113,.15);color:var(--red);border:1px solid rgba(248,113,113,.3)}
.btn:hover{opacity:.85}
.btn-row{display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap}
.hint{font-size:.72rem;color:var(--gray-500);margin-top:.25rem}
</style>
</head>
<body>
<header>
  <h1>🦆 DigitalDuck Admin — Prácticos</h1>
  <nav class="header-nav">
    <a href="index.php">← Panel</a>
    <a href="laminas.php">Láminas</a>
    <a href="../practicos.html" target="_blank">Ver sitio</a>
    <a href="logout.php">Cerrar sesión</a>
  </nav>
</header>
<div class="layout">
<aside class="sidebar">
  <a href="?new=1"><button class="btn-new">+ Nuevo práctico</button></a>
  <h2>Prácticos (<?= count($practicos) ?>)</h2>
  <?php foreach ($practicos as $p): ?>
  <div class="item <?= $practico_id==$p['id']?'active-item':'' ?>">
    <div>
      <a href="?practico_id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a>
      <div class="num"><?= htmlspecialchars($p['num']) ?> — <?= htmlspecialchars($p['file']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</aside>
<main class="main">
  <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <?php if (isset($_GET['new']) || ($practico_id === 0 && !$edit)): ?>
  <div class="card">
    <h3>Nuevo práctico</h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_practico">
      <input type="hidden" name="id" value="0">
      <div class="grid3" style="margin-bottom:.75rem">
        <div class="field"><label>Archivo (ej. pra9.html)</label><input name="file" required placeholder="pra9.html"></div>
        <div class="field"><label>Número (ej. 09)</label><input name="num" required></div>
        <div class="field"><label>Ícono</label><input name="icon" placeholder="🔬"></div>
      </div>
      <div class="grid2" style="margin-bottom:.75rem">
        <div class="field"><label>Nombre corto (menú)</label><input name="name" required></div>
        <div class="field"><label>Título completo</label><input name="title" required></div>
      </div>
      <div class="field" style="margin-bottom:.75rem"><label>Descripción</label><textarea name="description"></textarea></div>
      <div class="field" style="margin-bottom:.75rem"><label>Orden</label><input name="orden" type="number" value="<?= count($practicos)+1 ?>"></div>
      <div class="field" style="margin-bottom:.75rem">
        <label>Archivo .html del práctico (opcional — lo publica directo en el sitio)</label>
        <input type="file" name="html_file" accept=".html">
        <p class="hint">Si lo omites, solo se crea la entrada en el índice; puedes subir el .html generado más tarde editando este práctico.</p>
      </div>
      <div class="btn-row"><button type="submit" class="btn btn-yellow">Guardar práctico</button></div>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($edit): ?>
  <div class="card">
    <h3>Editar: <?= htmlspecialchars($edit['name']) ?></h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_practico">
      <input type="hidden" name="id" value="<?= $edit['id'] ?>">
      <div class="grid3" style="margin-bottom:.75rem">
        <div class="field"><label>Archivo</label><input name="file" required value="<?= htmlspecialchars($edit['file']) ?>"></div>
        <div class="field"><label>Número</label><input name="num" required value="<?= htmlspecialchars($edit['num']) ?>"></div>
        <div class="field"><label>Ícono</label><input name="icon" value="<?= htmlspecialchars($edit['icon']) ?>"></div>
      </div>
      <div class="grid2" style="margin-bottom:.75rem">
        <div class="field"><label>Nombre corto (menú)</label><input name="name" required value="<?= htmlspecialchars($edit['name']) ?>"></div>
        <div class="field"><label>Título completo</label><input name="title" required value="<?= htmlspecialchars($edit['title']) ?>"></div>
      </div>
      <div class="field" style="margin-bottom:.75rem"><label>Descripción</label><textarea name="description"><?= htmlspecialchars($edit['description']) ?></textarea></div>
      <div class="field" style="margin-bottom:.75rem"><label>Orden</label><input name="orden" type="number" value="<?= $edit['orden'] ?>"></div>
      <div class="field" style="margin-bottom:.75rem">
        <label>Reemplazar archivo .html del práctico (opcional)</label>
        <input type="file" name="html_file" accept=".html">
        <p class="hint">Sube una nueva versión del archivo para sobrescribir <?= htmlspecialchars($edit['file']) ?> en el sitio.</p>
      </div>
      <div class="btn-row">
        <button type="submit" class="btn btn-yellow">Guardar cambios</button>
      </div>
    </form>
    <form method="POST" style="margin-top:.5rem">
      <input type="hidden" name="action" value="delete_practico">
      <input type="hidden" name="id" value="<?= $edit['id'] ?>">
      <button class="btn btn-del" onclick="return confirm('¿Eliminar este práctico del índice? El archivo .html en el sitio no se borra.')">🗑 Eliminar del índice</button>
    </form>
  </div>
  <?php endif; ?>
</main>
</div>
</body>
</html>
