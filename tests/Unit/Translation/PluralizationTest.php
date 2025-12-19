<?php

namespace Tests\Unit\Translation;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

class PluralizationTest extends TestCase
{
    public function test_trans_choice_works_with_current_locale_english(): void
    {
        // Set locale to English
        App::setLocale('en');

        // Test with 0 items
        $translation = trans_choice('messages.items_count', 0);
        $this->assertEquals('No items', $translation);

        // Test with 1 item
        $translation = trans_choice('messages.items_count', 1);
        $this->assertEquals('1 item', $translation);

        // Test with multiple items
        $translation = trans_choice('messages.items_count', 5);
        $this->assertEquals('5 items', $translation);
    }

    public function test_trans_choice_works_with_current_locale_indonesian(): void
    {
        // Set locale to Indonesian
        App::setLocale('id');

        // Test with 0 items
        $translation = trans_choice('messages.items_count', 0);
        $this->assertEquals('Tidak ada item', $translation);

        // Test with 1 item
        $translation = trans_choice('messages.items_count', 1);
        $this->assertEquals('1 item', $translation);

        // Test with multiple items
        $translation = trans_choice('messages.items_count', 5);
        $this->assertEquals('5 item', $translation);
    }

    public function test_pluralization_with_different_counts(): void
    {
        App::setLocale('en');

        // Test users count
        $this->assertEquals('No users', trans_choice('messages.users_count', 0));
        $this->assertEquals('One user', trans_choice('messages.users_count', 1));
        $this->assertEquals('2 users', trans_choice('messages.users_count', 2));
        $this->assertEquals('10 users', trans_choice('messages.users_count', 10));
    }

    public function test_pluralization_with_time_units(): void
    {
        App::setLocale('en');

        // Test minutes
        $this->assertEquals('1 minute', trans_choice('messages.minutes', 1));
        $this->assertEquals('5 minutes', trans_choice('messages.minutes', 5));

        // Test hours
        $this->assertEquals('1 hour', trans_choice('messages.hours', 1));
        $this->assertEquals('3 hours', trans_choice('messages.hours', 3));

        // Test days
        $this->assertEquals('1 day', trans_choice('messages.days', 1));
        $this->assertEquals('7 days', trans_choice('messages.days', 7));
    }

    public function test_pluralization_changes_with_locale(): void
    {
        // Test with English
        App::setLocale('en');
        $englishPlural = trans_choice('messages.records_found', 5);

        // Test with Indonesian
        App::setLocale('id');
        $indonesianPlural = trans_choice('messages.records_found', 5);

        // They should be different
        $this->assertNotEquals($englishPlural, $indonesianPlural);
        $this->assertStringContainsString('5', $englishPlural);
        $this->assertStringContainsString('5', $indonesianPlural);
    }

    public function test_pluralization_with_zero_count(): void
    {
        App::setLocale('en');

        $translation = trans_choice('messages.records_found', 0);
        $this->assertEquals('No records found', $translation);

        App::setLocale('id');
        $translation = trans_choice('messages.records_found', 0);
        $this->assertEquals('Tidak ada record ditemukan', $translation);
    }

    public function test_pluralization_with_large_numbers(): void
    {
        App::setLocale('en');

        $translation = trans_choice('messages.items_count', 1000);
        $this->assertEquals('1000 items', $translation);

        $translation = trans_choice('messages.users_count', 999);
        $this->assertEquals('999 users', $translation);
    }
}
