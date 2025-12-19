<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Support\ApiResponse;
use App\Support\Traits\ProvidesMetadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Laporan & Statistik
 */
class ActivityLogController extends Controller
{
  use ApiResponse, ProvidesMetadata;

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

    // Get distinct filter values from database
    $filterOptions = $this->service->getFilterOptions();

    $metadata = $this->buildMetadata(
      allowedSorts: ["created_at", "log_name", "event"],
      filters: [
        "log_name" => [
          "label" => __("activity_logs.filters.log_name"),
          "type" => "select",
          "options" => $filterOptions["log_names"]
            ->map(
              fn($name) => [
                "value" => $name,
                "label" => $name,
              ],
            )
            ->values()
            ->all(),
        ],
        "browser" => [
          "label" => __("activity_logs.filters.browser"),
          "type" => "select",
          "options" => $filterOptions["browsers"]
            ->map(
              fn($browser) => [
                "value" => $browser,
                "label" => $browser,
              ],
            )
            ->values()
            ->all(),
        ],
        "device_type" => [
          "label" => __("activity_logs.filters.device_type"),
          "type" => "select",
          "options" => $this->buildSelectOptions([
            "desktop" => __("activity_logs.device_types.desktop"),
            "mobile" => __("activity_logs.device_types.mobile"),
            "tablet" => __("activity_logs.device_types.tablet"),
          ]),
        ],
        "created_at" => [
          "label" => __("activity_logs.filters.created_at"),
          "type" => "date_range",
        ],
      ],
      translationPrefix: "activity_logs", // Translation prefix for sorts
    );

    return $this->paginateResponse(
      $activities,
      __("messages.activity_logs.retrieved"),
      200,
      $metadata,
    );
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
      return $this->notFound(__("messages.activity_logs.not_found"));
    }

    return $this->success($activity, __("messages.activity_logs.item_retrieved"));
  }
}
