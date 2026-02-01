<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\Services\LoginThrottlingServiceInterface;

class LoginThrottlingService implements LoginThrottlingServiceInterface
{
    private const CACHE_TTL = 300;

    public function __construct(private readonly RateLimiter $rateLimiter) {}

    protected function makeKey(string $login, string $ip): string
    {
        $normalizedLogin = Str::lower(trim($login));

        return 'auth:login:'.sha1($normalizedLogin.'|'.$ip);
    }

    protected function lockKey(string $login): string
    {
        return 'auth:lock:'.sha1(Str::lower(trim($login)));
    }

    protected function failuresKey(string $login): string
    {
        return 'auth:failures:'.sha1(Str::lower(trim($login)));
    }

    protected function getConfig(): array
    {
        return Cache::remember('auth:throttle:config', self::CACHE_TTL, function () {
            return [
                'rate_limit_enabled' => (bool) \Modules\Common\Models\SystemSetting::get('auth.login_rate_limit_enabled', true),
                'max_attempts' => (int) \Modules\Common\Models\SystemSetting::get('auth.login_rate_limit_max_attempts', 5),
                'decay_minutes' => (int) \Modules\Common\Models\SystemSetting::get('auth.login_rate_limit_decay_minutes', 1),
                'lockout_enabled' => (bool) \Modules\Common\Models\SystemSetting::get('auth.lockout_enabled', true),
                'lockout_threshold' => (int) \Modules\Common\Models\SystemSetting::get('auth.lockout_failed_attempts_threshold', 5),
                'lockout_window' => (int) \Modules\Common\Models\SystemSetting::get('auth.lockout_window_minutes', 60),
                'lockout_duration' => (int) \Modules\Common\Models\SystemSetting::get('auth.lockout_duration_minutes', 15),
            ];
        });
    }

    public function ensureNotLocked(string $login): void
    {
        $lockKey = $this->lockKey($login);
        $unlockAtTs = Cache::get($lockKey);
        if ($unlockAtTs) {
            $remaining = max(0, $unlockAtTs - time());
            $config = $this->getConfig();
            $minutes = intdiv($remaining, 60);
            $seconds = $remaining % 60;
            $retryIn = $minutes > 0 ? ($minutes.' menit'.($seconds > 0 ? ' '.$seconds.' detik' : '')) : ($seconds.' detik');
            throw ValidationException::withMessages([
                'login' => "Akun terkunci sementara (gagal >= {$config['lockout_threshold']} kali dalam {$config['lockout_window']} menit). Coba lagi dalam {$retryIn}.",
            ]);
        }
    }

    public function tooManyAttempts(string $login, string $ip): bool
    {
        $config = $this->getConfig();
        if (! $config['rate_limit_enabled']) {
            return false;
        }

        $key = $this->makeKey($login, $ip);

        return $this->rateLimiter->tooManyAttempts($key, $config['max_attempts']);
    }

    public function hitAttempt(string $login, string $ip): void
    {
        $config = $this->getConfig();
        if (! $config['rate_limit_enabled']) {
            return;
        }

        $key = $this->makeKey($login, $ip);
        $this->rateLimiter->hit($key, $config['decay_minutes'] * 60);
    }

    public function clearAttempts(string $login, string $ip): void
    {
        $key = $this->makeKey($login, $ip);
        $this->rateLimiter->clear($key);
    }

    public function recordFailureAndMaybeLock(string $login): void
    {
        $config = $this->getConfig();
        if (! $config['lockout_enabled']) {
            return;
        }

        $failKey = $this->failuresKey($login);
        $current = (int) (Cache::get($failKey) ?? 0);
        $current++;
        Cache::put($failKey, $current, now()->addMinutes($config['lockout_window']));

        if ($current >= $config['lockout_threshold']) {
            $unlockAt = now()->addMinutes($config['lockout_duration'])->timestamp;
            Cache::put($this->lockKey($login), $unlockAt, now()->addMinutes($config['lockout_duration']));
            Cache::forget($failKey);
        }
    }

    public function getRetryAfterSeconds(string $login, string $ip): int
    {
        $key = $this->makeKey($login, $ip);

        return $this->rateLimiter->availableIn($key);
    }

    public function getRateLimitConfig(): array
    {
        $config = $this->getConfig();

        return [
            'max' => $config['max_attempts'],
            'decay' => $config['decay_minutes'],
        ];
    }

    public function getLockRemainingSeconds(string $login): int
    {
        $unlockAtTs = Cache::get($this->lockKey($login));

        return $unlockAtTs ? max(0, $unlockAtTs - time()) : 0;
    }

    public function getLockConfig(): array
    {
        $config = $this->getConfig();

        return [
            'threshold' => $config['lockout_threshold'],
            'window' => $config['lockout_window'],
            'duration' => $config['lockout_duration'],
        ];
    }
}
