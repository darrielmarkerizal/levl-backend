<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Laporan & Statistik
 */
class ActivityLogController extends Controller
{
  use ApiResponse;

  public function __construct(private ActivityLogService $service) {}

  /**
   * Daftar Log Aktivitas
   *
   * @authenticated
   */
  public function index(Request $request): JsonResponse
  {
    $perPage = max(1, min((int) $request->input("per_page", 15), 100));
    $activities = $this->service->paginate($perPage);

    return $this->paginateResponse($activities, __('messages.activity_logs.retrieved'));
  }

  /**
   * Detail Log Aktivitas
   *
   * @authenticated
   */
  public function show(int $id): JsonResponse
  {
    $activity = $this->service->find($id);

    if (!$activity) {
      return $this->notFound(__('messages.activity_logs.not_found'));
    }

    return $this->success($activity, __('messages.activity_logs.item_retrieved'));
  }
}
