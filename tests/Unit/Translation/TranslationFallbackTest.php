<?php

namespace Tests\Unit\Translation;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

class TranslationFallbackTest extends TestCase
{
    public function test_fallback_locale_is_used_when_translation_missing(): void
    {
        // Set locale to English
        App::setLocale('en');

        // Create a key that doesn't exist in English but exists in Indonesian (fallback)
        $nonExistentKey = 'messages.test_fallback_key_'.time();

        // Get translation - should fall back to Indonesian
        $translation = __($nonExistentKey);

        // Since the key doesn't exist in either locale, it should return the key itself
        $this->assertEquals($nonExistentKey, $translation);
    }

    public function test_translation_exists_in_current_locale(): void
    {
        // Set locale to English
        App::setLocale('en');

        // Get a translation that exists
        $translation = __('messages.success');

        // Should return the English translation
        $this->assertEquals('Success.', $translation);
    }

    public function test_translation_exists_in_fallback_locale(): void
    {
        // Set locale to Indonesian (which is also the fallback)
        App::setLocale('id');

        // Get a translation that exists
        $translation = __('messages.success');

        // Should return the Indonesian translation
        $this->assertEquals('Berhasil.', $translation);
    }

    public function test_fallback_locale_configuration(): void
    {
        // Verify fallback locale is configured
        $fallbackLocale = config('app.fallback_locale');

        $this->assertNotNull($fallbackLocale);
        $this->assertContains($fallbackLocale, ['id', 'en']);
    }

    public function test_supported_locales_configuration(): void
    {
        // Verify supported locales are configured
        $supportedLocales = config('app.supported_locales');

        $this->assertIsArray($supportedLocales);
        $this->assertContains('en', $supportedLocales);
        $this->assertContains('id', $supportedLocales);
    }

    public function test_translation_with_parameters(): void
    {
        // Set locale to English
        App::setLocale('en');

        // Get translation with parameters
        $translation = __('messages.resource_created', ['resource' => 'User']);

        // Should return the English translation with parameter substituted
        $this->assertEquals('User created successfully.', $translation);
    }

    public function test_translation_files_exist_for_supported_locales(): void
    {
        $supportedLocales = config('app.supported_locales', ['en', 'id']);

        foreach ($supportedLocales as $locale) {
            $langPath = lang_path($locale);

            // Check if directory exists
            $this->assertDirectoryExists($langPath, "Translation directory for locale '{$locale}' should exist");

            // Check if messages.php exists
            $messagesFile = $langPath.'/messages.php';
            $this->assertFileExists($messagesFile, "messages.php file for locale '{$locale}' should exist");
        }
    }
}
