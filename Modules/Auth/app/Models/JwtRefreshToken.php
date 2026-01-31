<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JwtRefreshToken extends Model
{
    use HasFactory;

    protected $table = 'jwt_refresh_tokens';

    protected $fillable = [
        'user_id',
        'device_id',
        'token',
        'replaced_by',
        'ip',
        'user_agent',
        'revoked_at',
        'last_used_at',
        'expires_at',
        'idle_expires_at',
        'absolute_expires_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'replaced_by' => 'integer',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'idle_expires_at' => 'datetime',
        'absolute_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(JwtRefreshToken::class, 'replaced_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        if ($this->absolute_expires_at && $this->absolute_expires_at->isPast()) {
            return true;
        }

        if ($this->idle_expires_at && $this->idle_expires_at->isPast()) {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isReplaced(): bool
    {
        return $this->replaced_by !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired() && ! $this->isReplaced();
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    public function scopeValid($query)
    {
        return $query->whereNull('revoked_at')
            ->whereNull('replaced_by')
            ->where(function ($q) {
                $q->whereNull('absolute_expires_at')
                    ->orWhere('absolute_expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('idle_expires_at')
                    ->orWhere('idle_expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
