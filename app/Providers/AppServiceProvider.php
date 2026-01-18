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
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    $this->configureRateLimiting();

    // Register observers
    \App\Models\ActivityLog::observe(\App\Observers\ActivityLogObserver::class);

    // Slow query monitoring for Auth module
    if ($this->app->environment('local', 'staging')) {
      \Illuminate\Support\Facades\DB::listen(function ($query) {
        // Log queries >50ms
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
  }

  /**
   * Configure the rate limiters for the application.
   */
  protected function configureRateLimiting(): void
  {
    // Default API rate limiter
    RateLimiter::for("api", function (Request $request) {
      $config = config("rate-limiting.api.default");

      return Limit::perMinutes($config["decay"], $config["max"])->by(
        $request->user()?->id ?: $request->ip(),
      );
    });

    // Auth endpoints rate limiter (more restrictive)
    RateLimiter::for("auth", function (Request $request) {
      $config = config("rate-limiting.api.auth");

      return Limit::perMinutes($config["decay"], $config["max"])->by($request->ip());
    });

    // Enrollment endpoints rate limiter (most restrictive)
    RateLimiter::for("enrollment", function (Request $request) {
      $config = config("rate-limiting.api.enrollment");

      return Limit::perMinutes($config["decay"], $config["max"])->by(
        $request->user()?->id ?: $request->ip(),
      );
    });
  }


}
