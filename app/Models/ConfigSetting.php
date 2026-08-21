<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigSetting extends Model
{
    protected $table = 'config_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'group', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        if (!$setting) {
            return $default;
        }
        // Auto-cast boolean strings
        $val = $setting->value;
        if ($val === 'true' || $val === '1') {
            return true;
        }
        if ($val === 'false' || $val === '0') {
            return false;
        }
        return $val;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value]
        );
    }
}
