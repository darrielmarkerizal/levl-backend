<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

$preloadClasses = [
    Illuminate\Foundation\Application::class,
    Illuminate\Container\Container::class,
    Illuminate\Support\ServiceProvider::class,
    Illuminate\Routing\Router::class,
    Illuminate\Http\Request::class,
    Illuminate\Http\Response::class,
    Illuminate\Http\JsonResponse::class,
    Illuminate\Database\Eloquent\Model::class,
    Illuminate\Database\Eloquent\Builder::class,
    Illuminate\Database\Query\Builder::class,
    Illuminate\Support\Collection::class,
    Illuminate\Support\Facades\Facade::class,
    Illuminate\Pipeline\Pipeline::class,
    Illuminate\Contracts\Container\Container::class,
    Illuminate\Auth\AuthManager::class,
    Illuminate\Cache\CacheManager::class,
    Illuminate\Database\DatabaseManager::class,

    Laravel\Octane\Octane::class,

    Spatie\QueryBuilder\QueryBuilder::class,
    Spatie\Permission\Models\Role::class,
    Spatie\Permission\Models\Permission::class,

    Tymon\JWTAuth\JWTAuth::class,
    Tymon\JWTAuth\JWTGuard::class,
];

foreach ($preloadClasses as $class) {
    if (class_exists($class)) {
        try {
            (new ReflectionClass($class))->getFileName();
        } catch (Throwable) {
            continue;
        }
    }
}

$preloadPaths = [
    __DIR__.'/app/Support',
    __DIR__.'/app/Exceptions',
    __DIR__.'/app/Contracts',
];

foreach ($preloadPaths as $path) {
    if (! is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            try {
                require_once $file->getPathname();
            } catch (Throwable) {
                continue;
            }
        }
    }
}
