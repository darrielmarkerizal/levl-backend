<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterDataStoreRequest;
use App\Http\Requests\MasterDataUpdateRequest;
use App\Services\MasterDataService;
use App\Support\ApiResponse;
use App\Support\Traits\ProvidesMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @tags Data Master
 */
class MasterDataCrudController extends Controller
{
  use ApiResponse, ProvidesMetadata;

  public function __construct(private readonly MasterDataService $service) {}

  /**
   * Daftar Tipe Master Data
   *
   * @authenticated
   */
  public function types(Request $request)
  {
    $perPage = max(1, min((int) $request->get("per_page", 15), 100));

    $paginator = $this->service->getTypesPaginated($request->all(), $perPage);

    $metadata = $this->buildMetadata(
      allowedSorts: ["label", "key", "count", "last_updated"],
      filters: [
        "is_crud" => [
          "label" => __("master_data.filters.type"),
          "type" => "boolean",
          "options" => $this->buildBooleanOptions(
            __("master_data.filter_options.crud"),
            __("master_data.filter_options.enum"),
          ),
        ],
      ],
    );

    return $this->paginateResponse(
      $paginator,
      __("messages.master_data.types_retrieved"),
      200,
      $metadata,
    );
  }

  /**
   * Daftar Item Master Data
   *
   * @authenticated
   */
  public function index(Request $request, string $type)
  {
    $perPage = max(1, (int) $request->get("per_page", 15));

    $query = \Spatie\QueryBuilder\QueryBuilder::for(\App\Models\MasterDataItem::class)
      ->where("type", $type)
      ->allowedFilters(["label", "value", \Spatie\QueryBuilder\AllowedFilter::exact("is_active")])
      ->allowedSorts(["label", "value", "created_at", "updated_at", "sort_order"])
      ->defaultSort("sort_order");

    // Handle Search
    if ($request->has("search") && $request->search) {
      $query->where("label", "like", "%{$request->search}%");
    }

    if ($request->get("all") === "true") {
      $data = $query->get();

      return $this->success(
        ["items" => $data],
        __("messages.master_data.items_retrieved", ["type" => $type]),
      );
    }

    $paginator = $query->paginate($perPage);

    // Auto-detect sorts and filters from QueryBuilder with translations
    $metadata = $this->buildMetadataFromQuery(
      $query,
      [
        "is_active" => [
          "label" => __("master_data.filters.is_active"),
          "type" => "boolean",
          "true_label" => __("master_data.filter_options.active"),
          "false_label" => __("master_data.filter_options.inactive"),
        ],
      ],
      "master_data", // Translation prefix for auto-translating sorts and filters
    );

    return $this->paginateResponse(
      $paginator,
      __("messages.master_data.items_retrieved", ["type" => $type]),
      200,
      $metadata,
    );
  }

  /**
   * Detail Master Data
   *
   * @authenticated
   */
  public function show(string $type, int $id)
  {
    $item = $this->service->find($type, $id);

    if (!$item) {
      return $this->error(__("messages.master_data.not_found"), 404);
    }

    return $this->success(["item" => $item], __("messages.master_data.item_retrieved"));
  }

  /**
   * Buat Master Data Baru
   *
   * @authenticated
   */
  public function store(MasterDataStoreRequest $request, string $type)
  {
    // Check if this is a CRUD type (database-backed) or enum type
    $allTypes = $this->service->getTypes();
    $typeInfo = $allTypes->firstWhere("key", $type);

    if (!$typeInfo || ($typeInfo["is_crud"] ?? true) === false) {
      return $this->error(__("messages.master_data.enum_cannot_create"), 403);
    }

    $validated = $request->validated();

    Log::info("Master data create request", [
      "type" => $type,
      "user_id" => auth()->id(),
      "data" => $validated,
    ]);

    // Check for duplicate
    if ($this->service->valueExists($type, $validated["value"])) {
      return $this->error(__("messages.master_data.value_exists"), 422);
    }

    $item = $this->service->create($type, $validated);

    return $this->created(["item" => $item], __("messages.master_data.created"));
  }

  /**
   * Perbarui Master Data
   *
   * @authenticated
   */
  public function update(MasterDataUpdateRequest $request, string $type, int $id)
  {
    // Check if this is a CRUD type (database-backed) or enum type
    $allTypes = $this->service->getTypes();
    $typeInfo = $allTypes->firstWhere("key", $type);

    if (!$typeInfo || ($typeInfo["is_crud"] ?? true) === false) {
      return $this->error(__("messages.master_data.enum_cannot_edit"), 403);
    }

    $validated = $request->validated();

    Log::info("Master data update request", [
      "type" => $type,
      "id" => $id,
      "user_id" => auth()->id(),
      "data" => $validated,
    ]);

    // Check for duplicate value if changing
    if (isset($validated["value"])) {
      if ($this->service->valueExists($type, $validated["value"], $id)) {
        return $this->error(__("messages.master_data.value_exists"), 422);
      }
    }

    $updated = $this->service->update($type, $id, $validated);

    if (!$updated) {
      return $this->error(__("messages.master_data.not_found"), 404);
    }

    return $this->success(["item" => $updated], __("messages.master_data.updated"));
  }

  /**
   * Hapus Master Data
   *
   * @authenticated
   */
  public function destroy(string $type, int $id)
  {
    // Check if this is a CRUD type (database-backed) or enum type
    $allTypes = $this->service->getTypes();
    $typeInfo = $allTypes->firstWhere("key", $type);

    if (!$typeInfo || ($typeInfo["is_crud"] ?? true) === false) {
      return $this->error(__("messages.master_data.enum_cannot_delete"), 403);
    }

    Log::info("Master data delete request", [
      "type" => $type,
      "id" => $id,
      "user_id" => auth()->id(),
    ]);

    $result = $this->service->delete($type, $id);

    if ($result === "not_found") {
      return $this->error(__("messages.master_data.not_found"), 404);
    }

    if ($result === "system_protected") {
      return $this->error(__("messages.master_data.system_protected"), 403);
    }

    return $this->success([], __("messages.master_data.deleted"));
  }
}
