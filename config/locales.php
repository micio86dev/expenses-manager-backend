<?php

declare(strict_types=1);

/*
 * Supported UI and API locales. Adding a language means adding one folder under
 * `lang/` and one entry in `supported`. The first match against `Accept-Language`
 * wins; `default` is used when nothing matches.
 */
return [
    'supported' => ['it', 'en'],
    'default' => 'it',
];
