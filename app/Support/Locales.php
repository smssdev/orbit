<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * The interface languages Orbit speaks, and the bits the frontend needs to render them.
 */
final class Locales
{
    /**
     * @var array<string, array{name: string, dir: 'ltr'|'rtl'}>
     */
    public const array SUPPORTED = [
        'en' => ['name' => 'English', 'dir' => 'ltr'],
        'ar' => ['name' => 'العربية', 'dir' => 'rtl'],
    ];

    public const string COOKIE = 'locale';

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, self::SUPPORTED);
    }

    /**
     * @return 'ltr'|'rtl'
     */
    public static function direction(string $locale): string
    {
        return self::SUPPORTED[$locale]['dir'] ?? 'ltr';
    }

    /**
     * The JSON translations (lang/{locale}.json) the frontend's t() reads. English is the source language, so its
     * keys are the strings themselves and nothing needs to be sent.
     *
     * @return array<string, string>
     */
    public static function translations(string $locale): array
    {
        if ($locale === 'en') {
            return [];
        }

        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            return [];
        }

        /** @var array<string, string> $translations */
        $translations = once(fn (): array => (array) json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR));

        return $translations;
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (string $code, array $locale): array => ['code' => $code, 'name' => $locale['name']],
            array_keys(self::SUPPORTED),
            self::SUPPORTED,
        );
    }
}
