<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    private static ?array $cache = null;

    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        if (!Schema::hasTable('settings')) {
            self::$cache = [];
            return self::$cache;
        }

        $settings = Setting::query()->get(['key', 'value', 'type']);
        $map = [];
        foreach ($settings as $setting) {
            $map[$setting->key] = [
                'value' => $setting->value,
                'type' => $setting->type,
            ];
        }

        self::$cache = $map;
        return self::$cache;
    }

    public static function get(string $key, $default = null)
    {
        $settings = self::load();
        if (!array_key_exists($key, $settings)) {
            return $default;
        }

        $value = $settings[$key]['value'];
        $type = $settings[$key]['type'] ?? null;

        return self::cast($value, $type, $default);
    }

    public static function set(string $key, $value, ?string $type = null): void
    {
        // Treat null as empty string (Laravel ConvertEmptyStringsToNull converts "" to null)
        if ($value === null) {
            $value = '';
        }

        $resolvedType = $type ?? self::inferType($value);
        $storedValue = self::normalizeValue($value, $resolvedType);

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'type' => $resolvedType]
        );

        self::$cache = null;
    }

    public static function setMany(array $items): void
    {
        foreach ($items as $item) {
            self::set($item['key'], $item['value'], $item['type'] ?? null);
        }
    }

    private static function inferType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value)) {
            return 'json';
        }
        return 'string';
    }

    private static function normalizeValue($value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'integer':
                return (string) ((int) $value);
            case 'float':
                return (string) ((float) $value);
            case 'json':
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            default:
                return (string) $value;
        }
    }

    private static function cast($value, ?string $type, $default = null)
    {
        if ($value === null) {
            return $default;
        }

        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                $decoded = json_decode($value, true);
                return $decoded === null ? $default : $decoded;
            default:
                return $value;
        }
    }
}
