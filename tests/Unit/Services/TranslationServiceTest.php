<?php

namespace Tests\Unit\Services;

use App\Services\TranslationService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    protected TranslationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TranslationService;
        Cache::flush();
    }

    public function test_trans_method_translates_with_current_locale(): void
    {
        App::setLocale('en');

        $translation = $this->service->trans('messages.success');

        $this->assertEquals('Success.', $translation);
    }

    public function test_trans_method_with_locale_override(): void
    {
        App::setLocale('en');

        // Override to Indonesian
        $translation = $this->service->trans('messages.success', [], 'id');

        $this->assertEquals('Berhasil.', $translation);

        // Current locale should still be English
        $this->assertEquals('en', App::getLocale());
    }

    public function test_trans_method_with_parameters(): void
    {
        App::setLocale('en');

        $translation = $this->service->trans('messages.resource_created', ['resource' => 'User']);

        $this->assertEquals('User created successfully.', $translation);
    }

    public function test_trans_choice_method_with_pluralization(): void
    {
        App::setLocale('en');

        $translation = $this->service->transChoice('messages.items_count', 0);
        $this->assertEquals('No items', $translation);

        $translation = $this->service->transChoice('messages.items_count', 1);
        $this->assertEquals('1 item', $translation);

        $translation = $this->service->transChoice('messages.items_count', 5);
        $this->assertEquals('5 items', $translation);
    }

    public function test_trans_choice_with_locale_override(): void
    {
        App::setLocale('en');

        // Override to Indonesian
        $translation = $this->service->transChoice('messages.items_count', 5, [], 'id');

        $this->assertEquals('5 item', $translation);

        // Current locale should still be English
        $this->assertEquals('en', App::getLocale());
    }

    public function test_has_translation_method(): void
    {
        App::setLocale('en');

        $this->assertTrue($this->service->hasTranslation('messages.success'));
        $this->assertFalse($this->service->hasTranslation('messages.nonexistent_key_'.time()));
    }

    public function test_has_translation_with_locale_override(): void
    {
        App::setLocale('en');

        $this->assertTrue($this->service->hasTranslation('messages.success', 'id'));
        $this->assertTrue($this->service->hasTranslation('messages.success', 'en'));
    }

    public function test_get_current_locale(): void
    {
        App::setLocale('en');
        $this->assertEquals('en', $this->service->getCurrentLocale());

        App::setLocale('id');
        $this->assertEquals('id', $this->service->getCurrentLocale());
    }

    public function test_get_supported_locales_from_config(): void
    {
        $locales = $this->service->getSupportedLocales(false);

        $this->assertIsArray($locales);
        $this->assertContains('en', $locales);
        $this->assertContains('id', $locales);
    }

    public function test_get_supported_locales_with_filesystem_scan(): void
    {
        $locales = $this->service->getSupportedLocales(true);

        $this->assertIsArray($locales);
        $this->assertContains('en', $locales);
        $this->assertContains('id', $locales);
    }

    public function test_get_fallback_locale(): void
    {
        $fallbackLocale = $this->service->getFallbackLocale();

        $this->assertEquals('id', $fallbackLocale);
    }

    public function test_is_locale_supported(): void
    {
        $this->assertTrue($this->service->isLocaleSupported('en'));
        $this->assertTrue($this->service->isLocaleSupported('id'));
        $this->assertFalse($this->service->isLocaleSupported('fr'));
        $this->assertFalse($this->service->isLocaleSupported('de'));
    }

    public function test_set_locale_method(): void
    {
        $result = $this->service->setLocale('en');
        $this->assertTrue($result);
        $this->assertEquals('en', App::getLocale());

        $result = $this->service->setLocale('id');
        $this->assertTrue($result);
        $this->assertEquals('id', App::getLocale());
    }

    public function test_set_locale_fails_for_unsupported_locale(): void
    {
        $currentLocale = App::getLocale();

        $result = $this->service->setLocale('fr');

        $this->assertFalse($result);
        $this->assertEquals($currentLocale, App::getLocale());
    }

    public function test_supported_locales_are_cached(): void
    {
        // First call should cache the result
        $locales1 = $this->service->getSupportedLocales(true);

        // Second call should use cache
        $locales2 = $this->service->getSupportedLocales(true);

        $this->assertEquals($locales1, $locales2);
    }
}
