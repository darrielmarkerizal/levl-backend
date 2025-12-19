<?php

namespace Tests\Unit\Validation;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationLocalizationTest extends TestCase
{
    public function test_validation_messages_use_current_locale(): void
    {
        // Set locale to English
        App::setLocale('en');

        $validator = Validator::make(
            ['email' => 'invalid-email'],
            ['email' => 'required|email']
        );

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();

        // Check that error message is in English
        $this->assertArrayHasKey('email', $errors);
        $this->assertStringContainsString('valid email', strtolower($errors['email'][0]));
    }

    public function test_validation_messages_use_indonesian_locale(): void
    {
        // Set locale to Indonesian
        App::setLocale('id');

        $validator = Validator::make(
            ['email' => 'invalid-email'],
            ['email' => 'required|email']
        );

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();

        // Check that error message is in Indonesian
        $this->assertArrayHasKey('email', $errors);
        // Indonesian validation message should contain "alamat" or "email" and "valid"
        $errorMessage = strtolower($errors['email'][0]);
        $this->assertTrue(
            str_contains($errorMessage, 'alamat') || str_contains($errorMessage, 'email'),
            "Error message should be in Indonesian: {$errors['email'][0]}"
        );
    }

    public function test_custom_validation_messages_support_localization(): void
    {
        // Set locale to English
        App::setLocale('en');

        $validator = Validator::make(
            ['name' => ''],
            ['name' => 'required'],
            [
                'name.required' => __('validation.required', ['attribute' => 'name']),
            ]
        );

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();

        $this->assertArrayHasKey('name', $errors);
        $this->assertNotEmpty($errors['name'][0]);
    }

    public function test_attribute_names_are_translated(): void
    {
        // Set locale to English
        App::setLocale('en');

        $validator = Validator::make(
            ['email' => ''],
            ['email' => 'required']
        );

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();

        // The attribute name should be included in the error message
        $this->assertArrayHasKey('email', $errors);
        $this->assertStringContainsString('email', strtolower($errors['email'][0]));
    }

    public function test_validation_with_custom_attribute_names(): void
    {
        // Set locale to English
        App::setLocale('en');

        $validator = Validator::make(
            ['user_email' => ''],
            ['user_email' => 'required'],
            [],
            ['user_email' => 'Email Address']
        );

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();

        $this->assertArrayHasKey('user_email', $errors);
        // Should use the custom attribute name
        $this->assertStringContainsString('email address', strtolower($errors['user_email'][0]));
    }

    public function test_validation_messages_change_with_locale(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'required|email'];

        // Test with English
        App::setLocale('en');
        $validatorEn = Validator::make($data, $rules);
        $validatorEn->fails();
        $errorsEn = $validatorEn->errors()->first('email');

        // Test with Indonesian
        App::setLocale('id');
        $validatorId = Validator::make($data, $rules);
        $validatorId->fails();
        $errorsId = $validatorId->errors()->first('email');

        // Error messages should be different
        $this->assertNotEquals($errorsEn, $errorsId, 'Validation messages should differ between locales');
    }

    public function test_validation_files_exist_for_supported_locales(): void
    {
        $supportedLocales = config('app.supported_locales', ['en', 'id']);

        foreach ($supportedLocales as $locale) {
            $validationFile = lang_path($locale.'/validation.php');
            $this->assertFileExists($validationFile, "validation.php file for locale '{$locale}' should exist");
        }
    }
}
