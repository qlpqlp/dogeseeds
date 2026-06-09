<?php

declare(strict_types=1);

class I18n
{
    private static array $strings = [];
    private static array $fallbackStrings = [];
    private static string $lang = 'en';

    public static function getLanguageMeta(): array
    {
        static $meta = null;
        if ($meta === null) {
            $meta = require ROOT_PATH . '/includes/languages.php';
        }
        return $meta;
    }

    public static function availableCodes(): array
    {
        return array_keys(self::getLanguageMeta());
    }

    public static function getSiteDefaultLanguage(): string
    {
        $available = self::availableCodes();
        $configured = trim(getSetting('default_language', 'en') ?? 'en');

        if ($configured === '' || !in_array($configured, $available, true)) {
            return 'en';
        }

        return $configured;
    }

    public static function init(?string $lang = null): void
    {
        $available = self::availableCodes();

        if ($lang && in_array($lang, $available, true)) {
            $_SESSION['lang'] = $lang;
            self::$lang = $lang;
        } elseif (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $available, true)) {
            self::$lang = $_SESSION['lang'];
        } else {
            self::$lang = self::getSiteDefaultLanguage();
        }

        $file = ROOT_PATH . '/lang/' . self::$lang . '.json';
        if (!file_exists($file)) {
            self::$lang = 'en';
            $file = ROOT_PATH . '/lang/en.json';
        }

        self::$strings = json_decode((string) file_get_contents($file), true) ?? [];

        $enFile = ROOT_PATH . '/lang/en.json';
        self::$fallbackStrings = self::$lang === 'en'
            ? self::$strings
            : (json_decode((string) file_get_contents($enFile), true) ?? []);
    }

    public static function setLang(string $lang): void
    {
        if (in_array($lang, self::availableCodes(), true)) {
            $_SESSION['lang'] = $lang;
            self::init($lang);
        }
    }

    public static function getLang(): string
    {
        return self::$lang;
    }

    public static function t(string $key, ?string $fallback = null): string
    {
        return self::$strings[$key]
            ?? self::$fallbackStrings[$key]
            ?? $fallback
            ?? $key;
    }

    public static function all(): array
    {
        return self::$strings;
    }
}
