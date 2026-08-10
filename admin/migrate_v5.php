<?php
error_reporting(0);
ob_start();

$log = [];
try {
    require_once __DIR__ . '/../api/db.php';
    $pdo = get_db();

    // Estructura clinical_patient (texto libre legado) en edad/sexo/tipoBiopsia/sitioBiopsia.
    // El texto que no encaja en esos campos (antecedentes, contexto) se antepone a
    // clinical_symptoms para no perder información clínica del caso.
    $updates = [
        'pra2-lamina-1' => ['edad'=>'73','sexo'=>'Varón','tipoBiopsia'=>'','sitioBiopsia'=>'esófago',
            'symptoms' => 'Reflujo gastroesofágico persistente'],
        'pra2-lamina-2' => ['edad'=>'29','sexo'=>'Mujer','tipoBiopsia'=>'','sitioBiopsia'=>'yeyuno',
            'symptoms' => 'Fatiga, pérdida de peso, diarrea tras consumo de gluten'],
        'pra2-lamina-3' => ['edad'=>'70','sexo'=>'Varón','tipoBiopsia'=>'','sitioBiopsia'=>'próstata',
            'symptoms' => 'Dificultad para iniciar micción, chorro débil, nicturia'],
        'pra2-lamina-4' => ['edad'=>'8','sexo'=>'Niño','tipoBiopsia'=>'','sitioBiopsia'=>'músculo',
            'symptoms' => 'Retraso motor, debilidad proximal, hipertrofia leve de pantorrillas, problemas respiratorios'],
        'pra3-lamina-1' => ['edad'=>'61','sexo'=>'Varón','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Bebedor social, obesidad. Dolor en cuadrante superior derecho'],
        'pra3-lamina-2' => ['edad'=>'45','sexo'=>'Mujer','tipoBiopsia'=>'','sitioBiopsia'=>'corteza renal',
            'symptoms' => 'En diálisis. Lesión renal aguda tras TAC con contraste, oliguria'],
        'pra3-lamina-3' => ['edad'=>'67','sexo'=>'Hombre','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Trabajador industrial y minero jubilado. Disnea de esfuerzo, tos crónica no productiva'],
        'pra4-lamina-1' => ['edad'=>'55','sexo'=>'Varón','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Hospitalizado por falla renal. HTA de 10 años, mal controlada. Falla renal aguda dos semanas posteriores a un infarto al miocardio tratado con trombólisis y stent.'],
        'pra4-lamina-2' => ['edad'=>'35','sexo'=>'Mujer','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Hospitalizada por falla hepática. Antecedentes de depresión no tratada. Presenta ictericia  y dolor en el cuadrante superior derecho del abdomen tras sobredosis intencional con paracetamol.'],
        'pra4-lamina-3' => ['edad'=>'60','sexo'=>'Hombre','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Fumador. Presenta tos persistente, fiebre, pérdida de peso y sudores nocturnos.'],
        'pra5-lamina-1' => ['edad'=>'20','sexo'=>'Varón','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Sin antecedentes médicos de importancia. Acude a urgencias por dolor abdominal localizado inicialmente en la región periumbilical, que en las últimas horas se ha desplazado al cuadrante inferior derecho. El dolor se acompaña de náuseas, vómitos y fiebre de 38.5°C.'],
        'pra5-lamina-2' => ['edad'=>'47','sexo'=>'Mujer','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Antecedentes de hepatitis B crónica diagnosticada hace 20 años y transplante ortotópico. Presenta fatiga, ictericia progresiva y molestias abdominales en el cuadrante superior derecho durante los últimos seis meses. Sin antecedentes de consumo problemático de alcohol ni obesidad.'],
        'pra6-lamina-1' => ['edad'=>'28','sexo'=>'Mujer','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Madre de lactante de 14 meses. Nódulo palpable en cuadrante superoexterno de mama izquierda, móvil, bien delimitado, no doloroso. Aparición hace 8 meses con crecimiento lento progresivo'],
        'pra6-lamina-2' => ['edad'=>'49','sexo'=>'Mujer','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Dolor abdominal intermitente, distensión abdominal, cambios en hábito intestinal con diarrea alternada con estreñimiento. Pérdida de peso de 4 kg en 6 meses. Heces más delgadas de lo normal.'],
        'pra6-lamina-3' => ['edad'=>'25','sexo'=>'Hombre','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Antecedentes familiares de leiomioma uterino (madre). Biopsia excisional de nódulo cutáneo doloroso en pierna derecha. Nódulo móvil al examen físico. Sin cambios de coloración, sangrado, ulceración o prurito. Antecedentes de nódulos cutáneos desde los 15 años. Paciente niega síntomas sistémicos como fiebre, pérdida de peso o malestar general'],
        'pra7-lamina-1' => ['edad'=>'60','sexo'=>'Hombre','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Presenta dolor abdominal persistente, acompañado de cambios en el hábito intestinal en los últimos meses, con predominio de estreñimiento. Ha experimentado una pérdida de peso de 5 kg en los últimos 4 meses sin modificaciones en su dieta.'],
        'pra7-lamina-2' => ['edad'=>'39','sexo'=>'Hombre','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Biopsia excisional de masa sospechosa, no móvil, localizada en hombro izquierdo'],
        'pra8-lamina-1' => ['edad'=>'53','sexo'=>'Mujer','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Presento dolor de cabeza de comienzo suave hasta convertirse en insoportable y punzante, de acuerdo con su relato. La paciente relata hipertensión arterial y dislipidemia, las que controla pobremenTe con dieta y farmacos. Su marido relata qu e se encuentra tomando haciendo dieta y tomando unas pastillas para bajar de peso, las qué resultan contener fentermina.'],
        'pra8-lamina-2' => ['edad'=>'61','sexo'=>'Hombre','tipoBiopsia'=>'','sitioBiopsia'=>'',
            'symptoms' => 'Presenta vértigo acompañado de náuseas y vómitos. El dolor inicio aproximadamente 6 horas de forma repentina e intensa que varía entre la zona abdominal y costados.'],
    ];

    $stmt = $pdo->prepare("UPDATE laminas SET clinical_edad=?, clinical_sexo=?, clinical_tipo_biopsia=?, clinical_sitio_biopsia=?, clinical_patient='', clinical_symptoms=? WHERE slug=?");
    foreach ($updates as $slug => $d) {
        $stmt->execute([
            $d['edad'], $d['sexo'],
            $d['tipoBiopsia'] ?: null, $d['sitioBiopsia'] ?: null,
            $d['symptoms'], $slug,
        ]);
        $log[] = $slug . ': ' . ($stmt->rowCount() ? 'actualizada' : 'sin cambios (¿slug no existe?)');
    }

    $left = (int) $pdo->query("SELECT COUNT(*) FROM laminas WHERE clinical_edad IS NULL AND clinical_patient IS NOT NULL AND clinical_patient != ''")->fetchColumn();
    $log[] = 'Láminas que aún dependen solo de clinical_patient (sin estructurar): ' . $left . ($left > 0 ? ' — probablemente las 3 nuevas u otras fuera de este lote.' : ' (ok).');

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
<title>DigitalDuck — Migración v5</title>
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
<h1>🦆 DigitalDuck — Migración v5: estructurar datos clínicos (edad/sexo/biopsia)</h1>
<p class="<?= $status ?>"><strong><?= strtoupper($status) ?></strong></p>
<ul>
<?php foreach ($log as $line): ?>
  <li class="<?= (strpos($line,'ERROR') !== false) ? 'error' : 'ok' ?>"><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
</ul>
<p><a href="laminas.php">→ Ir a Láminas</a></p>
</body>
</html>
