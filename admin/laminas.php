<?php
error_reporting(0);
ob_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/db.php';
$pdo = get_db();

function slugify(string $s): string {
    $s = trim($s);
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function lines_to_json(string $text): string {
    $items = array_values(array_filter(array_map('trim', explode("\n", $text)), fn($l) => $l !== ''));
    return json_encode($items, JSON_UNESCAPED_UNICODE);
}

function json_or_empty(string $text): string {
    $text = trim($text);
    if ($text === '') return '[]';
    json_decode($text);
    return json_last_error() === JSON_ERROR_NONE ? $text : '[]';
}

$curriculum = json_decode(file_get_contents(__DIR__ . '/../data/curriculum.json'), true)['unidades'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_lamina') {
        $id       = (int)($_POST['id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $slug     = trim($_POST['slug'] ?? '') ?: slugify($title);
        $practico = trim($_POST['practico'] ?? '') ?: null;
        $indice   = $_POST['indice_en_practico'] !== '' ? (int)$_POST['indice_en_practico'] : null;

        $unidad_numero = trim($_POST['unidad_numero'] ?? '');
        $tema_id       = trim($_POST['tema_id'] ?? '');
        $unidad_nombre = ''; $tema_nombre = '';
        foreach ($curriculum as $u) {
            if ($u['numero'] === $unidad_numero) {
                $unidad_nombre = $u['nombre'];
                foreach ($u['temas'] as $t) {
                    if ($t['id'] === $tema_id) { $tema_nombre = $t['nombre']; }
                }
            }
        }

        $concept        = trim($_POST['concept'] ?? '');
        $clinical_edad  = trim($_POST['clinical_edad'] ?? '');
        $clinical_sexo  = trim($_POST['clinical_sexo'] ?? '');
        $clinical_tipo  = trim($_POST['clinical_tipo_biopsia'] ?? '');
        $clinical_sitio = trim($_POST['clinical_sitio_biopsia'] ?? '');
        $clinical_pat   = trim($_POST['clinical_patient'] ?? '');
        $clinical_symp  = trim($_POST['clinical_symptoms'] ?? '');
        $labs_json      = json_or_empty($_POST['labs_json'] ?? '[]');
        $imagen_json    = json_or_empty($_POST['imagenologia_json'] ?? '[]');
        $links_json     = json_or_empty($_POST['links_json'] ?? '[]');
        $questions_json = lines_to_json($_POST['questions_text'] ?? '');
        $orden          = (int)($_POST['orden'] ?? 0);

        $params = [
            $slug, $practico, $indice, $unidad_numero ?: null, $unidad_nombre ?: null,
            $tema_id ?: null, $tema_nombre ?: null, $title, $concept ?: null,
            $clinical_edad ?: null, $clinical_sexo ?: null, $clinical_tipo ?: null, $clinical_sitio ?: null,
            $clinical_pat ?: null, $clinical_symp ?: null,
            $labs_json, $imagen_json, $questions_json, $links_json, $orden
        ];

        if ($id > 0) {
            $pdo->prepare("UPDATE laminas SET slug=?,practico=?,indice_en_practico=?,unidad_numero=?,unidad_nombre=?,
                tema_id=?,tema_nombre=?,title=?,concept=?,clinical_edad=?,clinical_sexo=?,clinical_tipo_biopsia=?,
                clinical_sitio_biopsia=?,clinical_patient=?,clinical_symptoms=?,labs_json=?,imagenologia_json=?,
                questions_json=?,links_json=?,orden=?,updated_at=datetime('now') WHERE id=?")
                ->execute([...$params, $id]);
            $msg = 'Lámina actualizada.';
        } else {
            $pdo->prepare("INSERT INTO laminas (slug,practico,indice_en_practico,unidad_numero,unidad_nombre,
                tema_id,tema_nombre,title,concept,clinical_edad,clinical_sexo,clinical_tipo_biopsia,
                clinical_sitio_biopsia,clinical_patient,clinical_symptoms,labs_json,imagenologia_json,
                questions_json,links_json,orden) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute($params);
            $id = (int)$pdo->lastInsertId();
            $msg = 'Lámina creada.';
        }
        header('Location: '.$_SERVER['PHP_SELF'].'?lamina_id='.$id.'&msg='.urlencode($msg));
        exit;
    }

    if ($action === 'delete_lamina') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM laminas WHERE id=?")->execute([$id]);
        header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('Lámina eliminada.'));
        exit;
    }
}

$lamina_id = (int)($_GET['lamina_id'] ?? 0);
$msg       = $_GET['msg'] ?? '';
$practicos = $pdo->query("SELECT file,name FROM practicos ORDER BY orden,id")->fetchAll();
$laminas   = $pdo->query("SELECT id,slug,title,practico,unidad_numero FROM laminas ORDER BY orden,id")->fetchAll();
$edit      = null;

if ($lamina_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM laminas WHERE id=?");
    $stmt->execute([$lamina_id]);
    $edit = $stmt->fetch();
}

$questions_text = '';
if ($edit) {
    $qs = json_decode($edit['questions_json'] ?? '[]', true) ?: [];
    $questions_text = implode("\n", $qs);
}

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DigitalDuck Admin — Láminas</title>
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
.layout{display:grid;grid-template-columns:280px 1fr;min-height:calc(100vh - 52px)}
.sidebar{background:var(--purple-mid);border-right:1px solid var(--gray-700);padding:1.25rem;max-height:calc(100vh - 52px);overflow-y:auto}
.sidebar h2{font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--gray-500);margin-bottom:.75rem}
.item{padding:.5rem .75rem;border-radius:var(--radius);margin-bottom:.25rem;font-size:.85rem}
.item.active-item{background:rgba(250,204,21,.15)}
.sub{font-size:.7rem;color:var(--gray-500)}
.btn-new{display:block;width:100%;text-align:center;background:var(--green);color:#0f2e1d;border:none;padding:.6rem;border-radius:var(--radius);cursor:pointer;font-size:.875rem;margin-bottom:1rem}
.main{padding:1.5rem 2rem;max-width:900px}
.msg{background:rgba(74,222,128,.15);border:1px solid var(--green);color:var(--green);padding:.6rem 1rem;border-radius:var(--radius);margin-bottom:1rem;font-size:.875rem}
.card{background:var(--purple-mid);border:1px solid var(--gray-700);border-radius:var(--radius);padding:1.25rem;margin-bottom:1.25rem}
.card h3{font-size:.9rem;color:var(--yellow);margin-bottom:1rem;text-transform:uppercase;letter-spacing:.05em}
.card .sub-h{font-size:.75rem;color:var(--gray-500);margin:1rem 0 .5rem}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem}
.grid4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:.75rem}
.field{display:flex;flex-direction:column;gap:.3rem;margin-bottom:.75rem}
label{font-size:.75rem;color:var(--gray-500)}
input,textarea,select{background:var(--purple);border:1px solid var(--gray-700);color:var(--gray-100);padding:.45rem .65rem;border-radius:6px;font-size:.875rem;width:100%}
textarea{resize:vertical;min-height:70px;font-family:inherit}
textarea.mono{font-family:monospace;font-size:.78rem;min-height:90px}
input:focus,textarea:focus,select:focus{outline:none;border-color:var(--yellow)}
.btn{padding:.5rem 1.1rem;border-radius:6px;border:none;cursor:pointer;font-size:.85rem}
.btn-yellow{background:var(--yellow);color:var(--purple);font-weight:600}
.btn-del{background:rgba(248,113,113,.15);color:var(--red);border:1px solid rgba(248,113,113,.3)}
.btn:hover{opacity:.85}
.btn-row{display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap}
.hint{font-size:.72rem;color:var(--gray-500);margin-top:.25rem}
</style>
</head>
<body>
<header>
  <h1>🦆 DigitalDuck Admin — Láminas</h1>
  <nav class="header-nav">
    <a href="index.php">← Panel</a>
    <a href="practicos.php">Prácticos</a>
    <a href="../catalogo.html" target="_blank">Ver sitio</a>
    <a href="logout.php">Cerrar sesión</a>
  </nav>
</header>
<div class="layout">
<aside class="sidebar">
  <a href="?new=1"><button class="btn-new">+ Nueva lámina</button></a>
  <h2>Láminas (<?= count($laminas) ?>)</h2>
  <?php foreach ($laminas as $l): ?>
  <div class="item <?= $lamina_id==$l['id']?'active-item':'' ?>">
    <a href="?lamina_id=<?= $l['id'] ?>"><?= htmlspecialchars($l['title']) ?></a>
    <div class="sub"><?= htmlspecialchars($l['practico'] ?: 'suelta') ?> · U<?= htmlspecialchars($l['unidad_numero'] ?? '—') ?></div>
  </div>
  <?php endforeach; ?>
</aside>
<main class="main">
  <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <?php if (isset($_GET['new']) || ($lamina_id === 0 && !$edit)): $edit = null; ?>
  <div class="card">
    <h3>Nueva lámina</h3>
    <?php include __DIR__ . '/_lamina_form.php'; ?>
  </div>
  <?php endif; ?>

  <?php if ($edit): ?>
  <div class="card">
    <h3>Editar: <?= htmlspecialchars($edit['title']) ?></h3>
    <?php include __DIR__ . '/_lamina_form.php'; ?>
  </div>
  <form method="POST" style="margin-top:-.5rem">
    <input type="hidden" name="action" value="delete_lamina">
    <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <button class="btn btn-del" onclick="return confirm('¿Eliminar esta lámina del catálogo?')">🗑 Eliminar lámina</button>
  </form>
  <?php endif; ?>
</main>
</div>
</body>
</html>
