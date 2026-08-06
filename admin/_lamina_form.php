<?php
// Parcial de formulario, incluido desde laminas.php (usa $edit, $curriculum, $practicos, $questions_text)
$v = fn($key, $default = '') => $edit ? htmlspecialchars($edit[$key] ?? $default) : $default;
$sel_practico = $edit['practico'] ?? '';
$sel_unidad   = $edit['unidad_numero'] ?? '';
$sel_tema     = $edit['tema_id'] ?? '';
?>
<form method="POST">
  <input type="hidden" name="action" value="save_lamina">
  <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

  <div class="grid2">
    <div class="field"><label>Título de la lámina</label><input name="title" required value="<?= $v('title') ?>"></div>
    <div class="field"><label>Concepto clave</label><input name="concept" value="<?= $v('concept') ?>"></div>
  </div>

  <div class="grid3">
    <div class="field">
      <label>Práctico (vacío = lámina suelta)</label>
      <select name="practico">
        <option value="">— Suelta / caso rápido —</option>
        <?php foreach ($practicos as $p): ?>
        <option value="<?= htmlspecialchars($p['file']) ?>" <?= $sel_practico === $p['file'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['file']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label>Índice en el práctico (0-based)</label><input name="indice_en_practico" type="number" value="<?= $v('indice_en_practico') ?>"></div>
    <div class="field"><label>Orden en catálogo</label><input name="orden" type="number" value="<?= $v('orden', 0) ?>"></div>
  </div>

  <div class="grid2">
    <div class="field">
      <label>Unidad</label>
      <select name="unidad_numero" id="unidad_numero" onchange="onUnidadChange()">
        <option value="">— Sin clasificar —</option>
        <?php foreach ($curriculum as $u): ?>
        <option value="<?= htmlspecialchars($u['numero']) ?>" <?= $sel_unidad === $u['numero'] ? 'selected' : '' ?>><?= htmlspecialchars($u['numero'] . ' — ' . $u['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Tema</label>
      <select name="tema_id" id="tema_id"></select>
    </div>
  </div>

  <p class="sub-h">Paciente (caso ficticio)</p>
  <div class="grid4">
    <div class="field"><label>Edad</label><input name="clinical_edad" value="<?= $v('clinical_edad') ?>"></div>
    <div class="field"><label>Sexo</label><input name="clinical_sexo" value="<?= $v('clinical_sexo') ?>"></div>
    <div class="field"><label>Tipo de biopsia</label><input name="clinical_tipo_biopsia" value="<?= $v('clinical_tipo_biopsia') ?>"></div>
    <div class="field"><label>Sitio de biopsia</label><input name="clinical_sitio_biopsia" value="<?= $v('clinical_sitio_biopsia') ?>"></div>
  </div>
  <div class="field"><label>Descripción libre del paciente (opcional, se usa si no hay edad/sexo)</label><input name="clinical_patient" value="<?= $v('clinical_patient') ?>"></div>
  <div class="field"><label>Síntomas</label><textarea name="clinical_symptoms"><?= $v('clinical_symptoms') ?></textarea></div>

  <p class="sub-h">Hallazgos (JSON: [{"value":"...","url":"..."}])</p>
  <div class="grid2">
    <div class="field"><label>Laboratorio</label><textarea class="mono" name="labs_json"><?= $v('labs_json', '[]') ?></textarea></div>
    <div class="field"><label>Imagenología</label><textarea class="mono" name="imagenologia_json"><?= $v('imagenologia_json', '[]') ?></textarea></div>
  </div>

  <div class="field"><label>Preguntas de análisis (una por línea)</label><textarea name="questions_text"><?= htmlspecialchars($questions_text ?? '') ?></textarea></div>

  <div class="field"><label>Enlaces (JSON: [{"label":"...","url":"..."}])</label><textarea class="mono" name="links_json"><?= $v('links_json', '[]') ?></textarea></div>

  <div class="btn-row"><button type="submit" class="btn btn-yellow">Guardar lámina</button></div>
</form>

<script>
(function() {
  const CURRICULUM = <?= json_encode($curriculum, JSON_UNESCAPED_UNICODE) ?>;
  const selUnidad = document.getElementById('unidad_numero');
  const selTema   = document.getElementById('tema_id');
  const currentTema = <?= json_encode($sel_tema) ?>;

  window.onUnidadChange = function(preserve) {
    const u = CURRICULUM.find(x => x.numero === selUnidad.value);
    const temas = u ? u.temas : [];
    selTema.innerHTML = '<option value="">— Sin tema —</option>' +
      temas.map(t => `<option value="${t.id}">${t.nombre}</option>`).join('');
    if (preserve && temas.some(t => t.id === currentTema)) {
      selTema.value = currentTema;
    }
  };
  onUnidadChange(true);
})();
</script>
