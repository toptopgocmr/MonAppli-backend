<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Réglages clé/valeur des passerelles de paiement (modifiables dans l'admin :
 * Finances › Passerelles de paiement). Une clé absente = valeur de config/.env.
 */
class PaymentGatewaySetting extends Model
{
    protected $fillable = ['key', 'value', 'updated_by'];

    protected const CACHE_KEY = 'payment_gateway_settings.all';

    public static function allValues(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            try {
                if (!Schema::hasTable('payment_gateway_settings')) {
                    return [];
                }
                return self::query()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                return []; // base indisponible → on retombe sur la config
            }
        });
    }

    public static function getValue(string $key, $default = null)
    {
        $all = self::allValues();
        return array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '' ? $all[$key] : $default;
    }

    public static function setValue(string $key, ?string $value, ?int $adminId = null): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $adminId]);
        Cache::forget(self::CACHE_KEY);
    }
}
