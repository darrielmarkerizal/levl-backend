<?php

namespace Tests\Unit\Foundation;

use App\Exceptions\BusinessException;
use App\Exceptions\DuplicateResourceException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\InvalidPasswordException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use Tests\TestCase;

class CustomExceptionsTest extends TestCase
{
    /**
     * Test ResourceNotFoundException has correct status code and error code.
     */
    public function test_resource_not_found_exception_has_correct_codes(): void
    {
        $exception = new ResourceNotFoundException('Test resource not found');

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals('RESOURCE_NOT_FOUND', $exception->getErrorCode());
        $this->assertEquals('Test resource not found', $exception->getMessage());
    }

    /**
     * Test ResourceNotFoundException uses default message when none provided.
     */
    public function test_resource_not_found_exception_uses_default_message(): void
    {
        $exception = new ResourceNotFoundException;

        $this->assertNotEmpty($exception->getMessage());
    }

    /**
     * Test ValidationException has correct status code and error code.
     */
    public function test_validation_exception_has_correct_codes(): void
    {
        $exception = new ValidationException('Validation failed');

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals('VALIDATION_ERROR', $exception->getErrorCode());
        $this->assertEquals('Validation failed', $exception->getMessage());
    }

    /**
     * Test UnauthorizedException has correct status code and error code.
     */
    public function test_unauthorized_exception_has_correct_codes(): void
    {
        $exception = new UnauthorizedException('Not authenticated');

        $this->assertEquals(401, $exception->getStatusCode());
        $this->assertEquals('UNAUTHORIZED', $exception->getErrorCode());
        $this->assertEquals('Not authenticated', $exception->getMessage());
    }

    /**
     * Test ForbiddenException has correct status code and error code.
     */
    public function test_forbidden_exception_has_correct_codes(): void
    {
        $exception = new ForbiddenException('Access denied');

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertEquals('FORBIDDEN', $exception->getErrorCode());
        $this->assertEquals('Access denied', $exception->getMessage());
    }

    /**
     * Test InvalidPasswordException has correct status code and error code.
     */
    public function test_invalid_password_exception_has_correct_codes(): void
    {
        $exception = new InvalidPasswordException('Invalid password');

        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEquals('INVALID_PASSWORD', $exception->getErrorCode());
        $this->assertEquals('Invalid password', $exception->getMessage());
    }

    /**
     * Test DuplicateResourceException has correct status code and error code.
     */
    public function test_duplicate_resource_exception_has_correct_codes(): void
    {
        $exception = new DuplicateResourceException('Resource already exists');

        $this->assertEquals(409, $exception->getStatusCode());
        $this->assertEquals('DUPLICATE_RESOURCE', $exception->getErrorCode());
        $this->assertEquals('Resource already exists', $exception->getMessage());
    }

    /**
     * Test all custom exceptions extend BusinessException.
     */
    public function test_all_custom_exceptions_extend_business_exception(): void
    {
        $exceptions = [
            new ResourceNotFoundException,
            new ValidationException,
            new UnauthorizedException,
            new ForbiddenException,
            new InvalidPasswordException,
            new DuplicateResourceException,
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(BusinessException::class, $exception);
        }
    }

    /**
     * Test exception status codes are unique and appropriate.
     */
    public function test_exception_status_codes_are_appropriate(): void
    {
        $expectedStatusCodes = [
            ResourceNotFoundException::class => 404,
            ValidationException::class => 422,
            UnauthorizedException::class => 401,
            ForbiddenException::class => 403,
            InvalidPasswordException::class => 400,
            DuplicateResourceException::class => 409,
        ];

        foreach ($expectedStatusCodes as $exceptionClass => $expectedCode) {
            $exception = new $exceptionClass;
            $this->assertEquals(
                $expectedCode,
                $exception->getStatusCode(),
                "{$exceptionClass} should have status code {$expectedCode}"
            );
        }
    }

    /**
     * Test exception error codes are unique.
     */
    public function test_exception_error_codes_are_unique(): void
    {
        $exceptions = [
            new ResourceNotFoundException,
            new ValidationException,
            new UnauthorizedException,
            new ForbiddenException,
            new InvalidPasswordException,
            new DuplicateResourceException,
        ];

        $errorCodes = array_map(fn ($e) => $e->getErrorCode(), $exceptions);
        $uniqueErrorCodes = array_unique($errorCodes);

        $this->assertCount(
            count($errorCodes),
            $uniqueErrorCodes,
            'All exception error codes should be unique'
        );
    }
}
