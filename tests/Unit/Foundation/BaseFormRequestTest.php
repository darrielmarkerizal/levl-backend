<?php

namespace Tests\Unit\Foundation;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Tests\TestCase;

class BaseFormRequestTest extends TestCase
{
    /**
     * Test that validation error format is standardized.
     */
    public function test_validation_error_format_is_standardized(): void
    {
        $request = new class extends BaseFormRequest
        {
            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return [
                    'title' => 'required|string|min:3',
                    'email' => 'required|email',
                ];
            }
        };

        $request->setContainer(app());
        $request->replace([
            'title' => 'ab', // Too short
            'email' => 'invalid-email',
        ]);

        try {
            $request->validateResolved();
            $this->fail('Expected HttpResponseException was not thrown');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
            $data = json_decode($response->getContent(), true);

            $this->assertEquals(422, $response->getStatusCode());
            $this->assertArrayHasKey('success', $data);
            $this->assertArrayHasKey('message', $data);
            $this->assertArrayHasKey('errors', $data);
            $this->assertFalse($data['success']);
            $this->assertIsArray($data['errors']);
        }
    }

    /**
     * Test that custom validation messages are applied.
     */
    public function test_custom_validation_messages_are_applied(): void
    {
        $request = new class extends BaseFormRequest
        {
            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return [
                    'email' => 'required|email',
                ];
            }
        };

        $request->setContainer(app());
        $request->replace([
            'email' => 'not-an-email',
        ]);

        try {
            $request->validateResolved();
            $this->fail('Expected HttpResponseException was not thrown');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
            $data = json_decode($response->getContent(), true);

            $this->assertArrayHasKey('email', $data['errors']);
            $this->assertNotEmpty($data['errors']['email']);
        }
    }

    /**
     * Test that validation passes with valid data.
     */
    public function test_validation_passes_with_valid_data(): void
    {
        $request = new class extends BaseFormRequest
        {
            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return [
                    'title' => 'required|string|min:3',
                    'email' => 'required|email',
                ];
            }
        };

        $request->setContainer(app());
        $request->replace([
            'title' => 'Valid Title',
            'email' => 'valid@example.com',
        ]);

        // Should not throw exception
        $request->validateResolved();
        $this->assertTrue(true);
    }

    /**
     * Test that common validation messages are available.
     */
    public function test_common_validation_messages_are_available(): void
    {
        $request = new class extends BaseFormRequest
        {
            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return [];
            }
        };

        $messages = $request->messages();

        $expectedKeys = [
            'required',
            'email',
            'unique',
            'string',
            'integer',
            'numeric',
            'array',
            'min.string',
            'max.string',
            'confirmed',
            'exists',
            'in',
            'date',
            'after',
            'before',
            'regex',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $messages, "Missing validation message key: {$key}");
        }
    }
}
