<?php

namespace App\Support\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Custom Path Generator for better URL caching and organization.
 *
 * Generates paths like: model-type/model-id/collection/media-uuid/
 * Example: users/123/avatar/a1b2c3d4-e5f6.../
 *
 * Benefits:
 * - CDN-friendly paths for better caching
 * - Consistent structure across all models
 * - UUID prevents file enumeration/guessing
 * - Collection-based organization for easy management
 */
class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive/';
    }

    protected function getBasePath(Media $media): string
    {
        // Get model type (e.g., 'users', 'courses', 'news')
        $modelType = $this->getModelTypeName($media->model_type);

        // Get model ID
        $modelId = $media->model_id;

        // Get collection name (e.g., 'avatar', 'thumbnail', 'banner')
        $collection = $media->collection_name;

        // Use UUID for unique identification (prevents guessing)
        $uuid = $media->uuid ?: $media->id;

        return "{$modelType}/{$modelId}/{$collection}/{$uuid}";
    }

    protected function getModelTypeName(string $modelClass): string
    {
        // Convert full class name to snake_case plural
        // e.g., "Modules\Auth\Models\User" -> "users"
        $className = class_basename($modelClass);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)).'s';
    }
}
