<?php

namespace SmartCms\Redirects\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property string $old_url
 * @property string $new_url
 * @property int $status_code
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Redirect extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    public function getTable()
    {
        return config('redirects.table_name', 'redirects');
    }

    public static function clearCache(): void
    {
        Cache::forget(config('redirects.cache.key', 'redirects_cache'));
    }
}
