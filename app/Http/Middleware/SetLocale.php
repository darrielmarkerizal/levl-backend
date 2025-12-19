<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);

        App::setLocale($locale);

        Log::info('Locale set for request', [
            'locale' => $locale,
            'request_id' => $request->id ?? uniqid(),
            'path' => $request->path(),
        ]);

        return $next($request);
    }

    /**
     * Detect the locale from the request.
     *
     * Priority order:
     * 1. lang query parameter
     * 2. Accept-Language header
     * 3. Default locale from config
     */
    private function detectLocale(Request $request): string
    {
        // Priority 1: Check lang query parameter
        if ($request->has('lang')) {
            $locale = $request->query('lang');
            if ($this->isSupported($locale)) {
                return $locale;
            }
        }

        // Priority 2: Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguageHeader($acceptLanguage);
            if ($locale && $this->isSupported($locale)) {
                return $locale;
            }
        }

        // Priority 3: Return default locale
        return config('app.locale', 'id');
    }

    /**
     * Parse the Accept-Language header and return the first supported locale.
     */
    private function parseAcceptLanguageHeader(string $header): ?string
    {
        // Parse Accept-Language header format: "en-US,en;q=0.9,id;q=0.8"
        $locales = [];

        // Split by comma to get individual language preferences
        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $part = trim($part);

            // Extract locale and quality value
            if (preg_match('/^([a-z]{2}(?:-[A-Z]{2})?)(?:;q=([0-9.]+))?$/i', $part, $matches)) {
                $locale = strtolower($matches[1]);
                $quality = isset($matches[2]) ? (float) $matches[2] : 1.0;

                // Extract just the language code (e.g., "en" from "en-US")
                if (str_contains($locale, '-')) {
                    $locale = explode('-', $locale)[0];
                }

                $locales[] = [
                    'locale' => $locale,
                    'quality' => $quality,
                ];
            }
        }

        // Sort by quality value (highest first)
        usort($locales, function ($a, $b) {
            return $b['quality'] <=> $a['quality'];
        });

        // Return the first supported locale
        foreach ($locales as $item) {
            if ($this->isSupported($item['locale'])) {
                return $item['locale'];
            }
        }

        return null;
    }

    /**
     * Check if a locale is supported.
     */
    private function isSupported(string $locale): bool
    {
        $supportedLocales = config('app.supported_locales', ['en', 'id']);

        return in_array($locale, $supportedLocales, true);
    }
}
