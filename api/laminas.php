<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';
$pdo = get_db();

$rows = $pdo->query("SELECT * FROM laminas ORDER BY orden, id")->fetchAll();

$laminas = array_map(function ($r) {
    return [
        'id'                => $r['slug'],
        'practico'          => $r['practico'],
        'indiceEnPractico'  => $r['indice_en_practico'] !== null ? (int) $r['indice_en_practico'] : null,
        'unidadNumero'      => $r['unidad_numero'],
        'unidadNombre'      => $r['unidad_nombre'],
        'temaId'            => $r['tema_id'],
        'temaNombre'        => $r['tema_nombre'],
        'title'             => $r['title'],
        'concept'           => $r['concept'],
        'clinical'          => [
            'edad'         => $r['clinical_edad'],
            'sexo'         => $r['clinical_sexo'],
            'tipoBiopsia'  => $r['clinical_tipo_biopsia'],
            'sitioBiopsia' => $r['clinical_sitio_biopsia'],
            'sample'       => $r['clinical_sample'] ?? null,
            'patient'      => $r['clinical_patient'],
            'symptoms'     => $r['clinical_symptoms'],
            'labs'         => json_decode($r['labs_json'] ?? '[]', true) ?: [],
            'imagenologia' => json_decode($r['imagenologia_json'] ?? '[]', true) ?: [],
        ],
        'questions'         => json_decode($r['questions_json'] ?? '[]', true) ?: [],
        'links'             => json_decode($r['links_json'] ?? '[]', true) ?: [],
    ];
}, $rows);

echo json_encode($laminas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
