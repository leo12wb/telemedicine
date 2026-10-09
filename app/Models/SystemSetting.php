<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $settings = Cache::remember('system_settings', 300, function () {
            return self::pluck('value', 'key')->all();
        });

        return $settings[$key] ?? $default;
    }

    public static function setMany(array $data): void
    {
        $rows = array_map(
            fn ($key, $value) => ['key' => $key, 'value' => $value],
            array_keys($data),
            array_values($data)
        );

        self::upsert($rows, ['key'], ['value', 'updated_at']);

        Cache::forget('system_settings');
    }
}
