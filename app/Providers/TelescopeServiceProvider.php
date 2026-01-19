<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
    class TelescopeServiceProvider extends \Laravel\Telescope\TelescopeApplicationServiceProvider
    {
        /**
         * Register any application services.
         */
        public function register(): void
        {
            // Telescope::night();

            $this->hideSensitiveRequestDetails();

            $isLocal = $this->app->environment('local');
            $telescopeEnabled = config('telescope.enabled', false);

            Telescope::filter(function (IncomingEntry $entry) use ($isLocal, $telescopeEnabled) {
                
                return $isLocal ||
                       $entry->isReportableException() ||
                       $entry->isFailedRequest() ||
                       $entry->isFailedJob() ||
                       $entry->isScheduledTask() ||
                       $entry->hasMonitoredTag();
            });

            Telescope::tag(function (IncomingEntry $entry) {
                if ($entry->type === 'request') {
                    $tags = ['status:'.$entry->content['response_status']];
                    
                    if (isset($entry->content['duration'])) {
                        $tags[] = 'time:'.$entry->content['duration'].'ms';
                    }
                    
                    if (isset($entry->content['memory'])) {
                        $tags[] = 'mem:'.$entry->content['memory'].'MB';
                    }

                    return $tags;
                }
                
                return [];
            });
        }

        /**
         * Prevent sensitive request details from being logged by Telescope.
         */
        protected function hideSensitiveRequestDetails(): void
        {
            if ($this->app->environment('local')) {
                return;
            }

            Telescope::hideRequestParameters(['_token']);

            Telescope::hideRequestHeaders([
                'cookie',
                'x-csrf-token',
                'x-xsrf-token',
            ]);
        }

        /**
         * Register the Telescope gate.
         *
         * This gate determines who can access Telescope in non-local environments.
         * 
         * WARNING: Currently set to allow public access without authentication.
         * This exposes sensitive application data including:
         * - Database queries and data
         * - Request/response with tokens
         * - Exception stack traces
         * - Cache keys and values
         * - Email content
         * 
         * RECOMMENDATION: Implement IP whitelist or basic auth for production.
         */
        protected function gate(): void
        {
            Gate::define('viewTelescope', function ($user = null) {
                // Allow everyone without authentication (NOT RECOMMENDED for production)
                return true;
            });
        }
    }
}
