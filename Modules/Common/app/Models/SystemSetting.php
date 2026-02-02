<?php

declare(strict_types=1);

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Common\Enums\SettingType;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    protected $casts = [
        'value' => 'string',
        'type' => SettingType::class,
    ];

    /**
     * Get the typed value based on the type field.
     */
    public function getTypedValueAttribute()
    {
        $type = $this->type instanceof SettingType ? $this->type->value : $this->type;

        return $this->castValueByType($type);
    }

    public function setValue($value, SettingType|string|null $type = null): void
    {
        $type = $this->resolveType($value, $type);

        $this->value = $this->encodeValueByType($value, $type);
        $this->type = $type;
    }

    protected function determineType($value): SettingType
    {
        return match (true) {
            is_array($value) || is_object($value) => SettingType::Json,
            is_bool($value) => SettingType::Boolean,
            is_numeric($value) => SettingType::Number,
            default => SettingType::String,
        };
    }

    private function castValueByType(string $type): mixed
    {
        return match ($type) {
            'number' => $this->castToNumber(),
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }

    private function castToNumber(): int|float
    {
        if (! is_numeric($this->value)) {
            return 0;
        }

        return str_contains($this->value, '.') ? (float) $this->value : (int) $this->value;
    }

    private function resolveType($value, SettingType|string|null $type): SettingType
    {
        if ($type === null) {
            return $this->determineType($value);
        }

        return is_string($type) ? SettingType::from($type) : $type;
    }

    private function encodeValueByType($value, SettingType $type): string
    {
        return match ($type) {
            SettingType::Json => json_encode($value),
            SettingType::Boolean => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (SystemSetting $setting) {
            \Illuminate\Support\Facades\Cache::forget('system_setting:' . $setting->key);
        });

        static::deleted(function (SystemSetting $setting) {
            \Illuminate\Support\Facades\Cache::forget('system_setting:' . $setting->key);
        });
    }

    /**
     * Get a setting by key.
     */
    public static function get(string $key, $default = null): mixed
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('system_setting:' . $key, function () use ($key, $default) {
            $setting = static::find($key);
            return $setting ? $setting->typed_value : $default;
        });
    }

    /**
     * Set a setting by key.
     */
    public static function set(string $key, $value): void
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => null, 'type' => SettingType::String]
        );

        if ($setting) {
            $setting->setValue($value);
            $setting->save();
            // Cache is cleared by the saved event observer
        }
    }
}
