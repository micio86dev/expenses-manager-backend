<?php

declare(strict_types=1);

/*
 * Names of the system categories, keyed by translation key. The reference seeder
 * stores the key; the API resolves the name for the request locale.
 */
return [
    'system' => [
        'groceries' => 'Spesa',
        'dining' => 'Ristoranti',
        'transport' => 'Trasporti',
        'housing' => 'Casa',
        'utilities' => 'Bollette',
        'health' => 'Salute',
        'leisure' => 'Tempo libero',
        'other' => 'Altro',
    ],

    'errors' => [
        'system_read_only' => 'Le categorie di sistema non possono essere modificate, eliminate o archiviate.',
        'in_use' => 'Questa categoria è usata da alcune spese. Archiviala invece di eliminarla.',
        'unavailable' => 'La categoria selezionata non esiste o è archiviata.',
    ],
];
