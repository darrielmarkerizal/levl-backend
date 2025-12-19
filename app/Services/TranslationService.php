<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class TranslationService
{
    /**
     * Translate a message key with optional locale override
     *
     * @param  string  $key  Translation key
     * @param  array  $params  Parameters for substitution
     * @param  string|null  $locale  Optional locale override
     * @return string Translated message
     */
    public function trans(string $key, array $params = [], ?string $locale = null): string
    {
        $currentLocale = App::getLocale();

        if ($locale && $locale !== $currentLocale) {
            // Temporarily switch locale
            App::setLocale($locale);
            $translation = __($key, $params);
            App::setLocale($currentLocale);

            return $translation;
        }

        return __($key, $params);
    }

    /**
     * Translate a message key with pluralization
     *
     * @param  string  $key  Translation key
     * @param  int  $count  Count for pluralization
     * @param  array  $params  Parameters for substitution
     * @param  string|null  $locale  Optional locale override
     * @return string Translated message with pluralization
     */
    public function transChoice(string $key, int $count, array $params = [], ?string $locale = null): string
    {
        $currentLocale = App::getLocale();

        if ($locale && $locale !== $currentLocale) {
            // Temporarily switch locale
            App::setLocale($locale);
            $translation = trans_choice($key, $count, $params);
            App::setLocale($currentLocale);

            return $translation;
        }

        return trans_choice($key, $count, $params);
    }

    /**
     * Check if a translation exists for a given key
     *
     * @param  string  $key  Translation key
     * @param  string|null  $locale  Optional locale to check
     * @return bool True if translation exists
     */
    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? App::getLocale();
        $currentLocale = App::getLocale();

        if ($locale !== $currentLocale) {
            App::setLocale($locale);
        }

        $translation = trans($key);
        $exists = $translation !== $key;

        if ($locale !== $currentLocale) {
            App::setLocale($currentLocale);
        }

        return $exists;
    }

    /**
     * Get the current application locale
     *
     * @return string Current locale code
     */
    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Get all supported locales from configuration and filesystem
     *
     * @param  bool  $scanFilesystem  Whether to scan filesystem for locales
     * @return array List of supported locale codes
     */
    public function getSupportedLocales(bool $scanFilesystem = true): array
    {
        // Use cache to avoid repeated filesystem scans
        $cacheKey = 'supported_locales_'.($scanFilesystem ? 'with_fs' : 'config_only');

        return cache()->remember($cacheKey, 3600, function () use ($scanFilesystem) {
            $configLocales = config('app.supported_locales', ['en', 'id']);

            if (! $scanFilesystem) {
                return $configLocales;
            }

            // Scan filesystem for additional locales
            $langPath = lang_path();
            $fileSystemLocales = [];

            if (File::isDirectory($langPath)) {
                $directories = File::directories($langPath);

                foreach ($directories as $directory) {
                    $locale = basename($directory);
                    // Only include if it has translation files
                    if ($this->hasTranslationFiles($locale)) {
                        $fileSystemLocales[] = $locale;
                    }
                }
            }

            // Merge and deduplicate
            $allLocales = array_unique(array_merge($configLocales, $fileSystemLocales));

            return array_values($allLocales);
        });
    }

    /**
     * Check if a locale has translation files
     *
     * @param  string  $locale  Locale code
     * @return bool True if locale has translation files
     */
    protected function hasTranslationFiles(string $locale): bool
    {
        $localePath = lang_path($locale);

        if (! File::isDirectory($localePath)) {
            return false;
        }

        // Check if directory has at least one PHP file
        $files = File::files($localePath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the fallback locale
     *
     * @return string Fallback locale code
     */
    public function getFallbackLocale(): string
    {
        return config('app.fallback_locale', 'id');
    }

    /**
     * Check if a locale is supported
     *
     * @param  string  $locale  Locale code to check
     * @return bool True if locale is supported
     */
    public function isLocaleSupported(string $locale): bool
    {
        return in_array($locale, $this->getSupportedLocales());
    }

    /**
     * Set the application locale
     *
     * @param  string  $locale  Locale code to set
     * @return bool True if locale was set successfully
     */
    public function setLocale(string $locale): bool
    {
        if ($this->isLocaleSupported($locale)) {
            App::setLocale($locale);

            return true;
        }

        return false;
    }
}
