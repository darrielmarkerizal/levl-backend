<?php

namespace Modules\Gamification\Providers;

use App\Support\Traits\RegistersModuleConfig;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Gamification\Services\GamificationService;
use Nwidart\Modules\Traits\PathNamespace;

class GamificationServiceProvider extends ServiceProvider
{
    use PathNamespace, RegistersModuleConfig;

    protected string $name = 'Gamification';

    protected string $nameLower = 'gamification';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerPolicies();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register policies.
     */
    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Gamification\Models\Challenge::class,
            \Modules\Gamification\Policies\ChallengePolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Gamification\Models\Badge::class,
            \Modules\Gamification\Policies\BadgePolicy::class
        );
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register Repository bindings
        $this->app->bind(
            \Modules\Gamification\Contracts\Repositories\GamificationRepositoryInterface::class,
            \Modules\Gamification\Repositories\GamificationRepository::class
        );
        $this->app->bind(
            \Modules\Gamification\Contracts\Repositories\ChallengeRepositoryInterface::class,
            \Modules\Gamification\Repositories\ChallengeRepository::class
        );
        $this->app->bind(
            \Modules\Gamification\Contracts\Repositories\UserGamificationStatRepositoryInterface::class,
            \Modules\Gamification\Repositories\UserGamificationStatRepository::class
        );
        $this->app->bind(
            \Modules\Gamification\Contracts\Repositories\UserBadgeRepositoryInterface::class,
            \Modules\Gamification\Repositories\UserBadgeRepository::class
        );
        $this->app->bind(
            \Modules\Gamification\Contracts\Repositories\PointRepositoryInterface::class,
            \Modules\Gamification\Repositories\PointRepository::class
        );

        // Register Service bindings
        $this->app->singleton(
            \Modules\Gamification\Contracts\Services\GamificationServiceInterface::class,
            \Modules\Gamification\Services\GamificationService::class
        );
        $this->app->singleton(
            \Modules\Gamification\Contracts\Services\ChallengeServiceInterface::class,
            \Modules\Gamification\Services\ChallengeService::class
        );
        $this->app->singleton(
            \Modules\Gamification\Contracts\Services\LeaderboardServiceInterface::class,
            \Modules\Gamification\Services\LeaderboardService::class
        );

        // Keep backward compatibility
        $this->app->singleton(GamificationService::class, function () {
            return new GamificationService;
        });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Gamification\Console\Commands\AssignDailyChallenges::class,
            \Modules\Gamification\Console\Commands\AssignWeeklyChallenges::class,
            \Modules\Gamification\Console\Commands\ExpireChallenges::class,
            \Modules\Gamification\Console\Commands\UpdateLeaderboard::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            $schedule->command('challenges:assign-daily')->dailyAt('00:01');
            $schedule->command('challenges:assign-weekly')->weeklyOn(1, '00:01');
            $schedule->command('challenges:expire')->hourly();
            $schedule->command('leaderboard:update')->everyFiveMinutes();
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->registerModuleConfig();
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
