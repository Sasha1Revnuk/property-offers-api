<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

final class TranslationHelper
{
    /**
     * @param array<string, string|int|float> $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $line = __($key, $replace);

        return is_string($line) ? $line : $key;
    }

    /**
     * @return array<string, string>
     */
    public static function getArray(string $key): array
    {
        return self::getArrayForLocale($key, app()->getLocale());
    }

    /**
     * @return array<string, string>
     */
    public static function getArrayForLocale(string $key, string $locale): array
    {
        $lines = Lang::get($key, [], $locale);

        if (! is_array($lines)) {
            return [];
        }

        $normalized = [];

        foreach ($lines as $lineKey => $line) {
            if (is_string($lineKey) && is_string($line)) {
                $normalized[$lineKey] = $line;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function localizedArrays(string ...$keys): array
    {
        $translations = [];

        foreach ([app()->getLocale()] as $locale) {
            $merged = [];

            foreach ($keys as $translationKey) {
                $merged = [
                    ...$merged,
                    ...self::getArrayForLocale($translationKey, $locale),
                ];
            }

            $translations[$locale] = $merged;
        }

        return $translations;
    }
}
