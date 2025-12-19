<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Extend the translator to add logging for missing translations
        $this->app->extend('translator', function (Translator $translator) {
            // Store original get method
            $originalGet = \Closure::bind(function ($key, array $replace = [], $locale = null, $fallback = true) {
                return $this->get($key, $replace, $locale, $fallback);
            }, $translator, Translator::class);

            // Override the get method to add logging
            $translator->macro('getWithLogging', function ($key, array $replace = [], $locale = null) use ($translator) {
                $locale = $locale ?: $translator->locale();
                $fallbackLocale = $translator->getFallback();

                // Try to get the translation
                $line = $translator->get($key, $replace, $locale, false);

                // If translation not found in current locale, log it
                if ($line === $key) {
                    // Check if it exists in fallback locale
                    $fallbackLine = $translator->get($key, $replace, $fallbackLocale, false);

                    if ($fallbackLine === $key) {
                        // Translation missing in both current and fallback locale
                        Log::warning('Missing translation key', [
                            'key' => $key,
                            'locale' => $locale,
                            'fallback_locale' => $fallbackLocale,
                            'request_id' => request()->id ?? null,
                        ]);
                    } else {
                        // Translation found in fallback locale
                        Log::info('Translation fallback used', [
                            'key' => $key,
                            'requested_locale' => $locale,
                            'fallback_locale' => $fallbackLocale,
                            'request_id' => request()->id ?? null,
                        ]);
                    }
                }

                return $line;
            });

            return $translator;
        });

        // Log when translation files are missing
        $this->app->booted(function () {
            $translator = app('translator');
            $supportedLocales = config('app.supported_locales', ['en', 'id']);

            foreach ($supportedLocales as $locale) {
                $langPath = lang_path($locale);

                if (! is_dir($langPath)) {
                    Log::error('Missing translation directory', [
                        'locale' => $locale,
                        'path' => $langPath,
                    ]);
                }
            }
        });
    }
}
