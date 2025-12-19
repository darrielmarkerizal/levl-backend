<?php

namespace Tests\Unit\Support;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    use ApiResponse;

    protected function setUp(): void
    {
        parent::setUp();

        // Set default locale to Indonesian for testing
        app()->setLocale('id');
    }

    /** @test */
    public function it_translates_success_message_with_translation_key()
    {
        $response = $this->success(null, 'messages.success');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);

        $this->assertEquals('Berhasil.', $data['message']);
        $this->assertTrue($data['success']);
    }

    /** @test */
    public function it_translates_success_message_with_english_locale()
    {
        app()->setLocale('en');

        $response = $this->success(null, 'messages.success');
        $data = $response->getData(true);

        $this->assertEquals('Success.', $data['message']);
    }

    /** @test */
    public function it_supports_backward_compatibility_with_plain_strings()
    {
        $response = $this->success(null, 'Custom success message');
        $data = $response->getData(true);

        $this->assertEquals('Custom success message', $data['message']);
    }

    /** @test */
    public function it_substitutes_parameters_in_translation()
    {
        $response = $this->success(null, 'messages.resource_created', ['resource' => 'User']);
        $data = $response->getData(true);

        $this->assertEquals('User berhasil dibuat.', $data['message']);
    }

    /** @test */
    public function it_translates_created_message()
    {
        $response = $this->created(['id' => 1], 'messages.created');
        $data = $response->getData(true);

        $this->assertEquals('Berhasil dibuat.', $data['message']);
        $this->assertEquals(201, $response->status());
    }

    /** @test */
    public function it_translates_error_message()
    {
        $response = $this->error('messages.error');
        $data = $response->getData(true);

        $this->assertEquals('Terjadi kesalahan.', $data['message']);
        $this->assertFalse($data['success']);
    }

    /** @test */
    public function it_translates_validation_error_message()
    {
        $errors = ['email' => ['Email is required']];
        $response = $this->validationError($errors, 'messages.validation_failed');
        $data = $response->getData(true);

        $this->assertEquals('Data yang Anda kirim tidak valid. Periksa kembali isian Anda.', $data['message']);
        $this->assertEquals(422, $response->status());
        $this->assertEquals($errors, $data['errors']);
    }

    /** @test */
    public function it_translates_not_found_message()
    {
        $response = $this->notFound('messages.not_found');
        $data = $response->getData(true);

        $this->assertEquals('Resource yang Anda cari tidak ditemukan.', $data['message']);
        $this->assertEquals(404, $response->status());
    }

    /** @test */
    public function it_translates_unauthorized_message()
    {
        $response = $this->unauthorized('messages.unauthorized');
        $data = $response->getData(true);

        $this->assertEquals('Anda tidak berhak mengakses resource ini.', $data['message']);
        $this->assertEquals(401, $response->status());
    }

    /** @test */
    public function it_translates_forbidden_message()
    {
        $response = $this->forbidden('messages.forbidden');
        $data = $response->getData(true);

        $this->assertEquals('Anda tidak memiliki izin untuk melakukan aksi ini.', $data['message']);
        $this->assertEquals(403, $response->status());
    }

    /** @test */
    public function it_uses_default_translation_keys_when_not_provided()
    {
        $response = $this->success();
        $data = $response->getData(true);

        $this->assertEquals('Berhasil.', $data['message']);
    }

    /** @test */
    public function it_maintains_response_structure_consistency()
    {
        app()->setLocale('id');
        $responseId = $this->success(['data' => 'test'], 'messages.success');
        $dataId = $responseId->getData(true);

        app()->setLocale('en');
        $responseEn = $this->success(['data' => 'test'], 'messages.success');
        $dataEn = $responseEn->getData(true);

        // Structure should be identical
        $this->assertSame(array_keys($dataId), array_keys($dataEn));
        $this->assertSame($dataId['data'], $dataEn['data']);

        // Only message should differ
        $this->assertNotEquals($dataId['message'], $dataEn['message']);
    }

    /** @test */
    public function it_translates_static_success_method()
    {
        $response = self::successStatic(null, 'messages.success');
        $data = $response->getData(true);

        $this->assertEquals('Berhasil.', $data['message']);
        $this->assertTrue($data['success']);
    }

    /** @test */
    public function it_translates_static_error_method()
    {
        $response = self::errorStatic('messages.error');
        $data = $response->getData(true);

        $this->assertEquals('Terjadi kesalahan.', $data['message']);
        $this->assertFalse($data['success']);
    }

    /** @test */
    public function it_supports_parameter_substitution_in_static_methods()
    {
        $response = self::successStatic(null, 'messages.resource_created', ['resource' => 'Post']);
        $data = $response->getData(true);

        $this->assertEquals('Post berhasil dibuat.', $data['message']);
    }

    /** @test */
    public function it_returns_key_when_translation_does_not_exist()
    {
        $response = $this->success(null, 'messages.nonexistent_key');
        $data = $response->getData(true);

        // Should return the key itself when translation doesn't exist
        $this->assertEquals('messages.nonexistent_key', $data['message']);
    }

    /** @test */
    public function it_handles_empty_parameters_array()
    {
        $response = $this->success(null, 'messages.success', []);
        $data = $response->getData(true);

        $this->assertEquals('Berhasil.', $data['message']);
    }
}
