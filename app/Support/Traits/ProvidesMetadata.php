<?php

namespace App\Support\Traits;

trait ProvidesMetadata
{
  /**
   * Build metadata for list endpoints
   *
   * @param array $allowedSorts Array of allowed sort field names
   * @param array $filters Array of filter configurations
   * @param string|null $translationPrefix Translation key prefix for auto-translating sorts/filters
   * @return array
   */
  protected function buildMetadata(
    array $allowedSorts = [],
    array $filters = [],
    ?string $translationPrefix = null,
  ): array {
    // Convert sorts to objects with field and label
    $sortsWithLabels = array_map(function ($sort) use ($translationPrefix) {
      return [
        "field" => $sort,
        "label" => $this->getTranslatedSortLabel($sort, $translationPrefix),
      ];
    }, $allowedSorts);

    return [
      "sorts" => $sortsWithLabels,
      "filters" => $this->buildFiltersMetadata($filters, $translationPrefix),
    ];
  }

  /**
   * Build metadata from QueryBuilder instance
   *
   * @param \Spatie\QueryBuilder\QueryBuilder $query
   * @param array $filterConfig Additional filter configuration (labels, enums, etc.)
   * @param string|null $translationPrefix Translation key prefix (e.g. 'activity_logs', 'master_data')
   * @return array
   */
  protected function buildMetadataFromQuery(
    \Spatie\QueryBuilder\QueryBuilder $query,
    array $filterConfig = [],
    ?string $translationPrefix = null,
  ): array {
    $extractor = app(\App\Services\QueryBuilderMetadataExtractor::class);
    $extracted = $extractor->extractMetadata($query, $translationPrefix);

    // Build filter metadata with additional config
    $filtersMetadata = [];
    foreach ($extracted["filters"] as $filterKey => $filterData) {
      $config = $filterConfig[$filterKey] ?? [];

      $filtersMetadata[$filterKey] = [
        "label" => $config["label"] ?? $filterData["label"],
        "type" => $this->resolveFilterDisplayType($filterData, $config),
        "options" => $this->resolveFilterOptions($config),
      ];
    }

    return [
      "sorts" => $extracted["sorts"],
      "filters" => $filtersMetadata,
    ];
  }

  /**
   * Build filters metadata with options
   *
   * @param array $filters
   * @param string|null $translationPrefix Translation key prefix
   * @return array
   */
  protected function buildFiltersMetadata(array $filters, ?string $translationPrefix = null): array
  {
    $metadata = [];

    foreach ($filters as $key => $config) {
      $metadata[$key] = [
        "label" =>
          $config["label"] ??
          $this->getTranslatedSortLabel(
            $key,
            $translationPrefix ? $translationPrefix . ".filters" : null,
          ),
        "type" => $config["type"] ?? "select",
        "options" => $config["options"] ?? [],
      ];
    }

    return $metadata;
  }

  /**
   * Get translated label for sort field or filter
   */
  private function getTranslatedSortLabel(string $field, ?string $translationPrefix = null): string
  {
    if ($translationPrefix) {
      $translationKey = $translationPrefix . "." . $field;
      $translated = __($translationKey);

      // If translation exists, use it
      if ($translated !== $translationKey) {
        return $translated;
      }
    }

    // Fallback to humanized field name
    return ucfirst(str_replace("_", " ", $field));
  }

  /**
   * Resolve filter display type (select, boolean, date_range, etc.)
   */
  private function resolveFilterDisplayType(array $filterData, array $config): string
  {
    // Explicit type from config
    if (isset($config["type"])) {
      return $config["type"];
    }

    // Detect boolean filters
    if (
      str_contains($filterData["name"], "is_") ||
      in_array($filterData["name"], ["active", "published", "enabled"])
    ) {
      return "boolean";
    }

    // Detect date filters
    if (str_contains($filterData["name"], "date") || str_contains($filterData["name"], "_at")) {
      return "date_range";
    }

    // Default to select
    return "select";
  }

  /**
   * Resolve filter options from configuration
   */
  private function resolveFilterOptions(array $config): array
  {
    // No options if not configured
    if (
      !isset($config["enum"]) &&
      !isset($config["options"]) &&
      !isset($config["query"]) &&
      !isset($config["type"])
    ) {
      return [];
    }

    // Explicit options array
    if (isset($config["options"])) {
      return is_array($config["options"]) ? $config["options"] : [];
    }

    // Enum class
    if (isset($config["enum"])) {
      return $this->resolveEnumOptions($config["enum"]);
    }

    // Database query
    if (isset($config["query"]) && is_callable($config["query"])) {
      return $config["query"]();
    }

    // Boolean type
    if (isset($config["type"]) && $config["type"] === "boolean") {
      return $this->buildBooleanOptions(
        $config["true_label"] ?? __("master_data.filter_options.active"),
        $config["false_label"] ?? __("master_data.filter_options.inactive"),
      );
    }

    return [];
  }

  /**
   * Resolve options from enum class
   */
  private function resolveEnumOptions(string $enumClass): array
  {
    if (!enum_exists($enumClass)) {
      return [];
    }

    return array_map(
      fn($case) => [
        "value" => $case->value,
        "label" => method_exists($case, "label") ? $case->label() : $case->name,
      ],
      $enumClass::cases(),
    );
  }

  /**
   * Build boolean filter options
   *
   * @param string $trueLabel
   * @param string $falseLabel
   * @return array
   */
  protected function buildBooleanOptions(string $trueLabel, string $falseLabel): array
  {
    return [["value" => true, "label" => $trueLabel], ["value" => false, "label" => $falseLabel]];
  }

  /**
   * Build select filter options from array
   *
   * @param array $options Array of value => label pairs
   * @return array
   */
  protected function buildSelectOptions(array $options): array
  {
    $result = [];
    foreach ($options as $value => $label) {
      $result[] = ["value" => $value, "label" => $label];
    }
    return $result;
  }
}
