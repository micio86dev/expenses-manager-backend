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
];
