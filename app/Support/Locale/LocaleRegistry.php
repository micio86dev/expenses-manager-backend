<?php

declare(strict_types=1);

namespace App\Support\Locale;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;

/**
 * Single source of truth for the supported locales (see config/locales.php).
 */
final readonly class LocaleRegistry
{
    public function __construct(private Repository $config) {}

    /**
     * @return list<string>
     */
    public function supported(): array
    {
        return array_values(array_filter(
            $this->config->array('locales.supported'),
            is_string(...),
        ));
    }

    public function default(): string
    {
        return $this->config->string('locales.default');
    }

    public function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, $this->supported(), true);
    }

    /**
     * First supported locale in `Accept-Language` order. Region subtags are
     * ignored: `en-GB` matches `en`. Returns null when nothing matches.
     */
    public function fromAcceptLanguage(Request $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $primary = strtolower(explode('_', str_replace('-', '_', $language))[0]);

            if ($this->isSupported($primary)) {
                return $primary;
            }
        }

        return null;
    }
}
