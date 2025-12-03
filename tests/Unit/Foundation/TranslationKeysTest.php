<?php

namespace Tests\Unit\Foundation;

use Tests\TestCase;

class TranslationKeysTest extends TestCase
{
    /**
     * Test that all required translation files exist for English.
     */
    public function test_english_translation_files_exist(): void
    {
        $requiredFiles = [
            'messages.php',
            'validation.php',
            'errors.php',
            'auth.php',
        ];

        foreach ($requiredFiles as $file) {
            $path = lang_path("en/{$file}");
            $this->assertFileExists($path, "English translation file {$file} does not exist");
        }
    }

    /**
     * Test that all required translation files exist for Indonesian.
     */
    public function test_indonesian_translation_files_exist(): void
    {
        $requiredFiles = [
            'messages.php',
            'validation.php',
            'errors.php',
            'auth.php',
        ];

        foreach ($requiredFiles as $file) {
            $path = lang_path("id/{$file}");
            $this->assertFileExists($path, "Indonesian translation file {$file} does not exist");
        }
    }

    /**
     * Test that common message keys exist in English.
     */
    public function test_common_message_keys_exist_in_english(): void
    {
        $requiredKeys = [
            'success',
            'created',
            'updated',
            'deleted',
            'not_found',
            'unauthorized',
            'forbidden',
            'validation_failed',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __("messages.{$key}", [], 'en');
            $this->assertNotEquals(
                "messages.{$key}",
                $translation,
                "Translation key 'messages.{$key}' does not exist in English"
            );
        }
    }

    /**
     * Test that common message keys exist in Indonesian.
     */
    public function test_common_message_keys_exist_in_indonesian(): void
    {
        $requiredKeys = [
            'success',
            'created',
            'updated',
            'deleted',
            'not_found',
            'unauthorized',
            'forbidden',
            'validation_failed',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __("messages.{$key}", [], 'id');
            $this->assertNotEquals(
                "messages.{$key}",
                $translation,
                "Translation key 'messages.{$key}' does not exist in Indonesian"
            );
        }
    }

    /**
     * Test that validation message keys exist in English.
     */
    public function test_validation_keys_exist_in_english(): void
    {
        $requiredKeys = [
            'required',
            'email',
            'unique',
            'string',
            'integer',
            'numeric',
            'array',
            'confirmed',
            'exists',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __("validation.{$key}", [], 'en');
            $this->assertNotEquals(
                "validation.{$key}",
                $translation,
                "Translation key 'validation.{$key}' does not exist in English"
            );
        }
    }

    /**
     * Test that validation message keys exist in Indonesian.
     */
    public function test_validation_keys_exist_in_indonesian(): void
    {
        $requiredKeys = [
            'required',
            'email',
            'unique',
            'string',
            'integer',
            'numeric',
            'array',
            'confirmed',
            'exists',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __("validation.{$key}", [], 'id');
            $this->assertNotEquals(
                "validation.{$key}",
                $translation,
                "Translation key 'validation.{$key}' does not exist in Indonesian"
            );
        }
    }

    /**
     * Test that error message keys exist in English.
     */
    public function test_error_keys_exist_in_english(): void
    {
        $requiredKeys = [
            'server_error',
            'not_found',
            'unauthorized',
            'forbidden',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __("errors.{$key}", [], 'en');
            $this->assertNotEquals(
                "errors.{$key}",
                $translation,
                "Translation key 'errors.{$key}' does not exist in English"
            );
        }
    }

    /**
     * Test that error message keys exist in Indonesian.
     */
    public function test_error_keys_exist_in_indonesian(): void
    {
        $requiredKeys = [
            'server_error',
            'not_found',
            'unauthorized',
            'forbidden',
        ];

        foreach ($requiredKeys as $key) {
            $translation = __("errors.{$key}", [], 'id');
            $this->assertNotEquals(
                "errors.{$key}",
                $translation,
                "Translation key 'errors.{$key}' does not exist in Indonesian"
            );
        }
    }

    /**
     * Test that both languages have the same translation keys in messages.
     */
    public function test_both_languages_have_same_message_keys(): void
    {
        $enMessages = require lang_path('en/messages.php');
        $idMessages = require lang_path('id/messages.php');

        $enKeys = array_keys($enMessages);
        $idKeys = array_keys($idMessages);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals(
            $enKeys,
            $idKeys,
            'English and Indonesian message files should have the same keys'
        );
    }

    /**
     * Test that both languages have the same translation keys in errors.
     */
    public function test_both_languages_have_same_error_keys(): void
    {
        $enErrors = require lang_path('en/errors.php');
        $idErrors = require lang_path('id/errors.php');

        $enKeys = array_keys($enErrors);
        $idKeys = array_keys($idErrors);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals(
            $enKeys,
            $idKeys,
            'English and Indonesian error files should have the same keys'
        );
    }

    /**
     * Test that translation files return arrays.
     */
    public function test_translation_files_return_arrays(): void
    {
        $files = [
            'en/messages.php',
            'en/validation.php',
            'en/errors.php',
            'en/auth.php',
            'id/messages.php',
            'id/validation.php',
            'id/errors.php',
            'id/auth.php',
        ];

        foreach ($files as $file) {
            $path = lang_path($file);
            $content = require $path;
            $this->assertIsArray($content, "Translation file {$file} should return an array");
        }
    }
}
