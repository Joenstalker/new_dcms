<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $connection = 'central';
    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): bool
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        $setting->value = self::encodeValue($value, $setting->type);
        return $setting->save();
    }

    /**
     * Get all settings grouped by group.
     */
    public static function getGroupedSettings(): array
    {
        $settings = static::all();
        $grouped = [];

        foreach ($settings as $setting) {
            $grouped[$setting->group][] = [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => self::castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        }

        return $grouped;
    }

    /**
     * Get settings by group.
     */
    public static function getByGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();

        return $settings->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => self::castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        })->toArray();
    }

    /**
     * Check if a setting is "Coming Soon" based on description.
     */
    public function isComingSoon(): bool
    {
        return str_contains($this->description ?? '', 'Coming Soon');
    }

    /**
     * Cast value based on type.
     */
    private static function castValue(?string $value, string $type): mixed
    {
        // Handle null values
        if ($value === null) {
            return null;
        }

        return match ($type) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int)$value,
                'json' => json_decode($value, true),
                default => $value,
            };
    }

    /**
     * Encode value for storage.
     */
    private static function encodeValue(mixed $value, string $type): string
    {
        return match ($type) {
                'boolean' => $value ? 'true' : 'false',
                'json' => json_encode($value),
                default => (string)$value,
            };
    }
}
