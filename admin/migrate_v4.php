<?php
error_reporting(0);
ob_start();

$log = [];
try {
    require_once __DIR__ . '/../api/db.php';
    $pdo = get_db();

    // Separa hallazgos de imagenología que quedaron mezclados dentro de labs_json
    // (arrastrados de cuando el generador aún no distinguía Laboratorio de Imagenología).
    // Cada entrada reemplaza labs_json e imagenologia_json completos de esa lámina.
    $updates = [
        'pra2-lamina-1' => [ // Metaplasia Intestinal
            'labs' => [],
            'imagenologia' => [
                ['value' => 'Esofagoscopia: cambios en mucosa esofágica', 'url' => null],
            ],
        ],
        'pra3-lamina-3' => [ // Antracosis Pulmonar
            'labs' => [
                ['value' => 'Función pulmonar alterada', 'url' => null],
            ],
            'imagenologia' => [
                ['value' => 'Radiografía: opacidades pulmonares', 'url' => null],
            ],
        ],
        'pra4-lamina-3' => [ // Tuberculosis Pulmonar
            'labs' => [
                ['value' => 'Prueba de tuberculina PPD - Positiva', 'url' => null],
                ['value' => 'Resultados de cultivo de esputo en espera', 'url' => null],
            ],
            'imagenologia' => [
                ['value' => 'Radiografía de tórax: múltiples lesiones pequeñas de distribución uniforme', 'url' => null],
            ],
        ],
        'pra5-lamina-1' => [ // Apendicitis Aguda
            'labs' => [
                ['value' => 'Hemograma: Neutrófilos 14,000/µL (vr: 4,000-11,000/µL)', 'url' => null],
                ['value' => 'Proteína C reactiva (PCR): 25 mg/L (vr: <5 mg/L)', 'url' => null],
            ],
            'imagenologia' => [
                ['value' => 'Ecografía abdominal: Apéndice no compresible, con diámetro de 8 mm, acompañado de aumento de la ecogenicidad del tejido graso periapendicular. No se observan fecalitos.', 'url' => null],
            ],
        ],
        'pra5-lamina-2' => [ // Cirrosis hepática
            'labs' => [
                ['value' => 'Bilirrubina total 3.2 mg/dL (vr: 0.1-1.2 mg/dL)', 'url' => null],
                ['value' => 'AST 85 U/L (vr: 10-40 U/L)', 'url' => null],
                ['value' => 'ALT 78 U/L (vr: 7-56 U/L)', 'url' => null],
                ['value' => 'Albúmina 2.8 g/dL (vr: 3.5-5.0 g/dL)', 'url' => null],
                ['value' => 'INR: 1.6 (vr: 0.8-1.2)', 'url' => null],
            ],
            'imagenologia' => [
                ['value' => 'Ecografía abdominal: Hígado de contornos irregulares con aumento de la ecogenicidad, signos de hipertensión portal, y esplenomegalia.', 'url' => null],
            ],
        ],
        'pra6-lamina-1' => [ // Fibroadenoma de mama
            'labs' => [],
            'imagenologia' => [
                ['value' => 'Mamografía: nódulo bien circunscrito', 'url' => null],
                ['value' => 'Ecografía: masa hipoecogénica homogénea con márgenes regulares', 'url' => null],
            ],
        ],
        'pra6-lamina-2' => [ // Polipo adenomatoso
            'labs' => [],
            'imagenologia' => [
                ['value' => 'Colonoscopía: lesión obstructiva en flexura esplénica del colon + múltiples pólipos distribuidos en todo el colon.', 'url' => null],
            ],
        ],
        'pra7-lamina-1' => [ // Adenocarcinoma de colon
            'labs' => [
                ['value' => 'Análisis genético: Mutación G12D en KRAS', 'url' => null],
            ],
            'imagenologia' => [
                ['value' => 'Colonoscopía: lesión infiltrativa', 'url' => null],
            ],
        ],
        'pra8-lamina-1' => [ // Cerebro
            'labs' => [],
            'imagenologia' => [
                ['value' => 'AngioCT en estudio', 'url' => null],
            ],
        ],
        'pra8-lamina-2' => [ // Aorta Abdominal
            'labs' => [],
            'imagenologia' => [
                ['value' => 'Resonancia magnética: Trombosis por placa ateromatosa de aorta abdominal', 'url' => null],
            ],
        ],
    ];

    $stmt = $pdo->prepare("UPDATE laminas SET labs_json = ?, imagenologia_json = ? WHERE slug = ?");
    foreach ($updates as $slug => $data) {
        $stmt->execute([
            json_encode($data['labs'], JSON_UNESCAPED_UNICODE),
            json_encode($data['imagenologia'], JSON_UNESCAPED_UNICODE),
            $slug,
        ]);
        $log[] = $slug . ': ' . ($stmt->rowCount() ? 'actualizada' : 'sin cambios (¿slug no existe?)');
    }

    // Chequeo de arrastre: labs_json que aún contenga términos típicos de imagenología
    // fuera de las láminas recién migradas.
    $slugsMigrados = "'" . implode("','", array_keys($updates)) . "'";
    $pendientes = $pdo->query("
        SELECT slug, title FROM laminas
        WHERE slug NOT IN ($slugsMigrados)
        AND (labs_json LIKE '%adiografía%' OR labs_json LIKE '%cografía%' OR labs_json LIKE '%esonancia%'
             OR labs_json LIKE '%amografía%' OR labs_json LIKE '%olonoscop%' OR labs_json LIKE '%ngioCT%'
             OR labs_json LIKE '%TAC%' OR labs_json LIKE '%scopia%')
    ")->fetchAll();
    if ($pendientes) {
        $log[] = 'ATENCIÓN — posibles hallazgos de imagen sin migrar en: ' . implode(', ', array_map(fn($r) => $r['slug'] . ' (' . $r['title'] . ')', $pendientes));
    } else {
        $log[] = 'Sin arrastres detectados en el resto del catálogo (ok).';
    }

    $status = 'ok';
} catch (Exception $e) {
    $log[] = 'ERROR: ' . $e->getMessage();
    $status = 'error';
}

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>DigitalDuck — Migración v4</title>
<style>
  body { font-family: monospace; background: #4c1d95; color: #f9f4f2; padding: 2rem; }
  h1   { color: #facc15; }
  .ok    { color: #4ade80; }
  .error { color: #f87171; }
  li   { margin: .3rem 0; }
  a { color: #facc15; }
</style>
</head>
<body>
<h1>🦆 DigitalDuck — Migración v4: separar Laboratorio / Imagenología</h1>
<p class="<?= $status ?>"><strong><?= strtoupper($status) ?></strong></p>
<ul>
<?php foreach ($log as $line): ?>
  <li class="<?= (strpos($line,'ERROR') !== false || strpos($line,'ATENCIÓN') !== false) ? 'error' : 'ok' ?>"><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
</ul>
<p><a href="laminas.php">→ Ir a Láminas</a></p>
</body>
</html>
