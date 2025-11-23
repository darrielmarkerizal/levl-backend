<?php

namespace App\Support\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

interface ApiResponseContract
{
  public function success(
    array $data = [],
    string $message = "Berhasil",
    int $status = 200,
  ): JsonResponse;

  public function created(array $data = [], string $message = "Berhasil dibuat"): JsonResponse;

  public function error(
    string $message = "Terjadi kesalahan",
    int $status = 400,
    ?array $errors = null,
  ): JsonResponse;

  public function paginateResponse(
    LengthAwarePaginator $paginator,
    string $message = "Berhasil",
  ): JsonResponse;

  public function validationError(array $errors): JsonResponse;

  public function notFound(string $message = "Resource tidak ditemukan"): JsonResponse;

  public function unauthorized(string $message = "Tidak terotorisasi"): JsonResponse;

  public function forbidden(string $message = "Akses ditolak"): JsonResponse;

  public function noContent(): JsonResponse;
}
