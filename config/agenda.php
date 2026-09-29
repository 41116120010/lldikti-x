<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jenis Pertemuan
    |--------------------------------------------------------------------------
    |
    | The field is deliberately free text — a meeting minute may legitimately be
    | "Workshop / Lokakarya" or something an agency invents — so this list is NOT
    | a validation allowlist. It is the datalist of suggestions rendered on the
    | create and edit forms, kept here so both forms offer the same vocabulary
    | instead of maintaining their own hardcoded copy.
    |
    */

    'jenis_rapat_suggestions' => [
        'Rapat Koordinasi',
        'Rapat Pleno',
        'Rapat Evaluasi & Monev',
        'Konsinyasi / FGD',
        'Rapat Terbatas / Pimpinan',
        'Sosialisasi / Bimtek',
        'Workshop / Lokakarya',
        'Pertemuan Lainnya',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default
    |--------------------------------------------------------------------------
    */

    'jenis_rapat_default' => 'Rapat Koordinasi',

    /*
    |--------------------------------------------------------------------------
    | Normalisasi Nilai Lama
    |--------------------------------------------------------------------------
    |
    | The column was an ENUM of lowercase keys until 2024_01_01_000010 widened it
    | to VARCHAR. Rows written before that change still hold the lowercase keys,
    | which the CSV export renders as "Koordinasi" while newer rows read
    | "Rapat Koordinasi" — two different words for the same meeting type in the
    | same official report. The data migration maps them onto the current
    | convention; the export stops calling ucfirst() on a value that is no longer
    | a lowercase key.
    |
    */

    'jenis_rapat_legacy_map' => [
        'koordinasi' => 'Rapat Koordinasi',
        'pleno' => 'Rapat Pleno',
        'evaluasi' => 'Rapat Evaluasi & Monev',
        'konsinyasi' => 'Konsinyasi / FGD',
        'terbatas' => 'Rapat Terbatas / Pimpinan',
        'lainnya' => 'Pertemuan Lainnya',
    ],

];
