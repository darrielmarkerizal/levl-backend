<?php

namespace App\Services;

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class QueryBuilderMetadataExtractor
{
  /**
   * Extract metadata from QueryBuilder instance
   *
   * @param string|null $translationPrefix Translation key prefix (e.g. 'activity_logs.sorts' or 'master_data.filters')
   */
  public function extractMetadata(QueryBuilder $query, ?string $translationPrefix = null): array
  {
    return [
      "sorts" => $this->extractAllowedSorts($query, $translationPrefix),
      "filters" => $this->extractAllowedFilters($query, $translationPrefix),
    ];
  }

  /**
   * Extract allowed sorts from QueryBuilder
   *
   * @param string|null $translationPrefix Translation key prefix for sort labels
   */
  private function extractAllowedSorts(
    QueryBuilder $query,
    ?string $translationPrefix = null,
  ): array {
    try {
      $reflection = new \ReflectionClass($query);
      $property = $reflection->getProperty("allowedSorts");
      $property->setAccessible(true);

      $allowedSorts = $property->getValue($query);

      if (!$allowedSorts) {
        return [];
      }

      // Convert AllowedSort objects to objects with field and label
      $sorts = [];
      foreach ($allowedSorts as $sort) {
        $sortField = null;
        if ($sort instanceof AllowedSort) {
          $sortField = $this->extractSortName($sort);
        } elseif (is_string($sort)) {
          $sortField = $sort;
        }

        if ($sortField) {
          $sorts[] = [
            "field" => $sortField,
            "label" => $this->getTranslatedLabel($sortField, $translationPrefix),
          ];
        }
      }

      return $sorts;
    } catch (\ReflectionException $e) {
      return [];
    }
  }

  /**
   * Extract sort name from AllowedSort object
   */
  private function extractSortName(AllowedSort $sort): string
  {
    try {
      $reflection = new \ReflectionClass($sort);
      $property = $reflection->getProperty("name");
      $property->setAccessible(true);

      return $property->getValue($sort);
    } catch (\ReflectionException $e) {
      return "";
    }
  }

  /**
   * Extract allowed filters from QueryBuilder
   *
   * @param string|null $translationPrefix Translation key prefix for filter labels
   */
  private function extractAllowedFilters(
    QueryBuilder $query,
    ?string $translationPrefix = null,
  ): array {
    try {
      $reflection = new \ReflectionClass($query);
      $property = $reflection->getProperty("allowedFilters");
      $property->setAccessible(true);

      $allowedFilters = $property->getValue($query);

      if (!$allowedFilters) {
        return [];
      }

      $filters = [];
      foreach ($allowedFilters as $filter) {
        if ($filter instanceof AllowedFilter) {
          $filterData = $this->extractFilterData($filter, $translationPrefix);
          if ($filterData) {
            $filters[$filterData["name"]] = $filterData;
          }
        } elseif (is_string($filter)) {
          $filters[$filter] = [
            "name" => $filter,
            "type" => "partial",
            "label" => $this->getTranslatedLabel($filter, $translationPrefix),
          ];
        }
      }

      return $filters;
    } catch (\ReflectionException $e) {
      return [];
    }
  }

  /**
   * Extract filter data from AllowedFilter object
   *
   * @param string|null $translationPrefix Translation key prefix
   */
  private function extractFilterData(
    AllowedFilter $filter,
    ?string $translationPrefix = null,
  ): ?array {
    try {
      $reflection = new \ReflectionClass($filter);

      // Get filter name
      $nameProperty = $reflection->getProperty("name");
      $nameProperty->setAccessible(true);
      $name = $nameProperty->getValue($filter);

      // Detect filter type based on filter class name
      $filterClassName = get_class($filter);
      $type = $this->detectFilterType($filterClassName, $filter);

      return [
        "name" => $name,
        "type" => $type,
        "label" => $this->getTranslatedLabel($name, $translationPrefix),
      ];
    } catch (\ReflectionException $e) {
      return null;
    }
  }

  /**
   * Get translated label for a field
   *
   * @param string $field Field name
   * @param string|null $translationPrefix Translation key prefix
   * @return string
   */
  private function getTranslatedLabel(string $field, ?string $translationPrefix = null): string
  {
    if ($translationPrefix) {
      $translationKey = $translationPrefix . "." . $field;

      // Try to translate
      $translated = __($translationKey);

      // If translation exists and is different from key, use it
      if ($translated !== $translationKey) {
        return $translated;
      }
    }

    // Fallback to humanized field name
    return ucfirst(str_replace("_", " ", $field));
  }

  /**
   * Detect filter type from AllowedFilter
   */
  private function detectFilterType(string $className, AllowedFilter $filter): string
  {
    // Check if it's an exact filter
    try {
      $reflection = new \ReflectionClass($filter);
      if ($reflection->hasProperty("filterClass")) {
        $property = $reflection->getProperty("filterClass");
        $property->setAccessible(true);
        $filterClass = $property->getValue($filter);

        // Make sure filterClass is a string
        if (is_string($filterClass)) {
          if (str_contains($filterClass, "FiltersExact")) {
            return "exact";
          }
          if (str_contains($filterClass, "FiltersPartial")) {
            return "partial";
          }
          if (str_contains($filterClass, "FiltersScope")) {
            return "scope";
          }
        }

        // If filterClass is an object, get its class name
        if (is_object($filterClass)) {
          $filterClassName = get_class($filterClass);
          if (str_contains($filterClassName, "FiltersExact")) {
            return "exact";
          }
          if (str_contains($filterClassName, "FiltersPartial")) {
            return "partial";
          }
          if (str_contains($filterClassName, "FiltersScope")) {
            return "scope";
          }
        }
      }
    } catch (\ReflectionException $e) {
      // Continue to default
    }

    return "partial"; // Default to partial
  }
}
