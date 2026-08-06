CREATE TABLE IF NOT EXISTS practicos (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    file        TEXT    NOT NULL UNIQUE,
    num         TEXT    NOT NULL,
    name        TEXT    NOT NULL,
    title       TEXT    NOT NULL,
    icon        TEXT,
    description TEXT,
    orden       INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS laminas (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    slug                TEXT    NOT NULL UNIQUE,
    practico            TEXT,                          -- FK laxo a practicos.file; NULL = lámina suelta (caso rápido)
    indice_en_practico  INTEGER,
    unidad_numero       TEXT,
    unidad_nombre       TEXT,
    tema_id             TEXT,
    tema_nombre         TEXT,
    title               TEXT    NOT NULL,
    concept             TEXT,
    clinical_edad       TEXT,
    clinical_sexo       TEXT,
    clinical_tipo_biopsia   TEXT,
    clinical_sitio_biopsia  TEXT,
    clinical_patient    TEXT,
    clinical_symptoms   TEXT,
    labs_json           TEXT,                          -- JSON: [{value,url}]
    imagenologia_json   TEXT,                           -- JSON: [{value,url}]
    questions_json       TEXT,                          -- JSON: [string]
    links_json          TEXT,                           -- JSON: [{label,url}]
    orden               INTEGER NOT NULL DEFAULT 0,
    created_at          TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at          TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (practico) REFERENCES practicos(file)
);
