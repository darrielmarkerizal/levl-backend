<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
  use ApiResponse;

  protected $dontFlash = ["current_password", "password", "password_confirmation"];

  public function register(): void
  {
    $this->reportable(function (Throwable $e) {
      //
    });
  }

  public function render($request, Throwable $e): Response
  {
    if ($request->expectsJson() || $request->is("api/*")) {
      return $this->handleApiException($request, $e);
    }

    return parent::render($request, $e);
  }

  protected function handleApiException(Request $request, Throwable $e): JsonResponse
  {
    if ($e instanceof ValidationException) {
      return $this->validationError($e->errors());
    }

    if ($e instanceof NotFoundHttpException) {
      return $this->notFound("Resource tidak ditemukan");
    }

    if ($e instanceof UnauthorizedHttpException) {
      return $this->unauthorized($e->getMessage() ?: "Tidak terotorisasi");
    }

    if ($e instanceof AccessDeniedHttpException) {
      return $this->forbidden($e->getMessage() ?: "Akses ditolak");
    }

    $statusCode = 500;
    if ($e instanceof HttpExceptionInterface || $e instanceof HttpException) {
      $statusCode = $e->getStatusCode();
    }

    $message = $e->getMessage() ?: "Terjadi kesalahan pada server";

    if (config("app.debug")) {
      return $this->error($message, $statusCode, [
        "exception" => get_class($e),
        "file" => $e->getFile(),
        "line" => $e->getLine(),
        "trace" => $e->getTraceAsString(),
      ]);
    }

    return $this->error($message, $statusCode);
  }
}
