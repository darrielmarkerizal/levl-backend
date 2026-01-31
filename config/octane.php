<?php

declare(strict_types=1);

use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CloseMonologHandlers;
use Laravel\Octane\Listeners\CollectGarbage;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
use Laravel\Octane\Listeners\FlushOnce;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\FlushUploadedFiles;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;
use Laravel\Octane\Octane;

$cpuCount = function_exists('swoole_cpu_num') ? swoole_cpu_num() : ((int) shell_exec('nproc 2>/dev/null') ?: 4);
$swooleHookAll = defined('SWOOLE_HOOK_ALL') ? SWOOLE_HOOK_ALL : 0;

return [

    'server' => env('OCTANE_SERVER', 'swoole'),

    'https' => env('OCTANE_HTTPS', false),

    'cache' => env('OCTANE_CACHE', true),

    'listeners' => [
        WorkerStarting::class => [
            EnsureUploadedFilesAreValid::class,
            EnsureUploadedFilesCanBeMoved::class,
        ],

        RequestReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            ...Octane::prepareApplicationForNextRequest(),
        ],

        RequestHandled::class => [
        ],

        RequestTerminated::class => [
            FlushUploadedFiles::class,
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
        ],

        TaskTerminated::class => [
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
        ],

        TickTerminated::class => [
        ],

        OperationTerminated::class => [
            FlushOnce::class,
            FlushTemporaryContainerInstances::class,
            DisconnectFromDatabases::class,
            CollectGarbage::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            CloseMonologHandlers::class,
        ],
    ],

    'warm' => [
        'cache',
        'cache.store',
        'config',
        'cookie',
        'db',
        'db.factory',
        'db.transactions',
        'encrypter',
        'files',
        'hash',
        'log',
        'router',
        'routes',
        'translator',
        'url',
        'validator',
        'view',
        'view.finder',
        'blade.compiler',
    ],

    'flush' => [
        'auth',
        'session',
        'session.store',
    ],

    'swoole' => [
        'options' => [
            'worker_num' => env('SWOOLE_WORKER_NUM', $cpuCount * 2),
            'task_worker_num' => env('SWOOLE_TASK_WORKER_NUM', $cpuCount),
            'reactor_num' => env('SWOOLE_REACTOR_NUM', $cpuCount),

            'max_request' => env('SWOOLE_MAX_REQUEST', 1000),
            'max_request_grace' => env('SWOOLE_MAX_REQUEST_GRACE', 100),

            'max_wait_time' => 60,
            'dispatch_mode' => 2,

            'enable_coroutine' => true,
            'hook_flags' => $swooleHookAll,
            'max_coroutine' => 1000,

            'log_file' => storage_path('logs/swoole_http.log'),
            'log_level' => env('SWOOLE_LOG_LEVEL', env('APP_ENV') === 'production' ? 2 : 4),

            'package_max_length' => 10 * 1024 * 1024,
            'buffer_output_size' => 2 * 1024 * 1024,
            'socket_buffer_size' => 8 * 1024 * 1024,

            'open_tcp_nodelay' => true,
            'tcp_fastopen' => true,
            'enable_reuse_port' => true,

            'http_parse_post' => true,
            'http_parse_cookie' => true,
            'http_compression' => true,
            'http_compression_level' => env('APP_ENV') === 'production' ? 1 : 0,

            'open_http2_protocol' => false,
            'open_cpu_affinity' => true,
        ],
    ],

    'tables' => [
    ],

    'watch' => [
        'app',
        'Modules',
        'bootstrap',
        'config',
        'database',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

    'garbage' => env('OCTANE_GARBAGE_COLLECTION', env('APP_ENV') === 'production' ? 256 : 128),

    'max_execution_time' => env('OCTANE_MAX_EXECUTION_TIME', 30),

];
