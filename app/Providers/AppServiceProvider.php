<?php

namespace App\Providers;

use App\Contracts\EnrollmentKeyHasherInterface;
use App\Support\EnrollmentKeyHasher;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->bind(EnrollmentKeyHasherInterface::class, EnrollmentKeyHasher::class);

    // Bind app-level interfaces to module implementations
    $this->app->bind(
      \App\Contracts\Services\ForumServiceInterface::class,
      \Modules\Forums\Services\ForumService::class
    );

    // Optimize service resolution in Octane
    if ($this->app->runningInConsole()) {
        // Only register heavy services in console mode
        $this->app->singleton('heavy.service', function ($app) {
            // Heavy service initialization
        });
    }
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    $this->configureRateLimiting();

    // Register observers efficiently
    \App\Models\ActivityLog::observe(\App\Observers\ActivityLogObserver::class);

    // Optimize query monitoring for Octane - only enable in development
    if ($this->app->environment('local') && !app()->bound(\Laravel\Octane\Octane::class)) {
      // Only enable slow query logging outside of Octane workers
      \Illuminate\Support\Facades\DB::listen(function ($query) {
        if ($query->time > 50) {
          \Illuminate\Support\Facades\Log::warning('Slow query detected in Auth module', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms',
            'url' => request()->fullUrl(),
          ]);
        }
      });
    }

    if ($this->app->environment("local")) {
      Mail::alwaysTo(config("mail.development_to", "dev@local.test"));
    }

    // Optimize view caching
    if (!$this->app->environment('local')) {
        $this->app['view']->getFinder()->setPaths(array_map(function ($path) {
            return $path;
        }, $this->app['view']->getFinder()->getPaths()));
    }
  }

  /**
   * Configure the rate limiters for the application.
   *
   * ⚠️ CURRENTLY SET TO UNLIMITED - FOR STRESS TEST ONLY ⚠️
   * Re-enable limits before production deployment!
   */
  protected function configureRateLimiting(): void
  {
    // Use optimized rate limiting for Octane
    RateLimiter::for("api", function (Request $request) {
      // Use a more efficient rate limiter in Octane
      if ($this->app->environment('testing')) {
        return Limit::none();
      }

      $key = $request->user()?->id ?: $request->ip();
      return Limit::perMinutes(1, 60)->by($key); // 60 requests per minute
    });

    RateLimiter::for("auth", function (Request $request) {
      if ($this->app->environment('testing')) {
        return Limit::none();
      }

      return Limit::perMinutes(1, 10)->by($request->ip()); // 10 auth attempts per minute
    });

    RateLimiter::for("enrollment", function (Request $request) {
      if ($this->app->environment('testing')) {
        return Limit::none();
      }

      $key = $request->user()?->id ?: $request->ip();
      return Limit::perMinutes(1, 5)->by($key); // 5 enrollment attempts per minute
    });
  }


}
