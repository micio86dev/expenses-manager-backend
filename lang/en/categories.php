<?php

declare(strict_types=1);

/*
 * Names of the system categories, keyed by translation key. The reference seeder
 * stores the key; the API resolves the name for the request locale.
 */
return [
    'system' => [
        'groceries' => 'Groceries',
        'dining' => 'Dining out',
        'transport' => 'Transport',
        'housing' => 'Housing',
        'utilities' => 'Utilities',
        'health' => 'Health',
        'leisure' => 'Leisure',
        'other' => 'Other',
    ],

    'errors' => [
        'system_read_only' => 'System categories cannot be modified, deleted or archived.',
        'in_use' => 'This category is used by expenses. Archive it instead of deleting it.',
        'unavailable' => 'The selected category does not exist or is archived.',
    ],
];
