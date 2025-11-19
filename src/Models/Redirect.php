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
 * @property int $hit_count
 * @property Carbon|null $last_hit_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Redirect extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'last_hit_at' => 'datetime',
        'hit_count' => 'integer',
        'status_code' => 'integer',
    ];

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

    /**
     * Track a hit on this redirect (increments hit_count and updates last_hit_at)
     */
    public function trackHit(): void
    {
        $this->increment('hit_count');
        $this->update(['last_hit_at' => now()]);
    }

    /**
     * Check if this redirect would create a loop
     */
    public function wouldCreateLoop(?int $ignoredId = null): bool
    {
        // Self-loop check: old_url === new_url
        if ($this->old_url === $this->new_url) {
            return true;
        }

        // Check for circular loops (A → B → ... → A)
        return $this->detectCircularLoop($this->new_url, $ignoredId);
    }

    /**
     * Recursively detect circular loops
     */
    protected function detectCircularLoop(string $url, ?int $ignoredId = null, array $visited = [], int $depth = 0): bool
    {
        // Prevent infinite recursion
        if ($depth > 10) {
            return true;
        }

        // If we've seen this URL before, we have a loop
        if (in_array($url, $visited)) {
            return true;
        }

        $visited[] = $url;

        // Find the next redirect in the chain
        $query = static::where('old_url', $url);

        if ($ignoredId !== null) {
            $query->where('id', '!=', $ignoredId);
        }

        $nextRedirect = $query->first();

        if (! $nextRedirect) {
            return false;
        }

        // If the next redirect points back to our original old_url, that's a loop
        if ($nextRedirect->new_url === $this->old_url) {
            return true;
        }

        // Continue checking the chain
        return $this->detectCircularLoop($nextRedirect->new_url, $ignoredId, $visited, $depth + 1);
    }
}
