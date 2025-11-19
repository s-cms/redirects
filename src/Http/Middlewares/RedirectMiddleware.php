<?php

namespace SmartCms\Redirects\Http\Middlewares;

use Closure;
use Illuminate\Support\Facades\Cache;
use SmartCms\Redirects\Models\Redirect;

class RedirectMiddleware
{
    public function handle($request, Closure $next)
    {
        $oldPath = $request->path();
        if (! str_starts_with($oldPath, '/')) {
            $oldPath = '/' . $oldPath;
        }

        $redirects = $this->getRedirects();
        if (isset($redirects[$oldPath])) {
            $redirect = $redirects[$oldPath];

            // Track the hit asynchronously to avoid slowing down the redirect
            $this->trackHit($redirect['id']);

            return redirect($redirect['new_url'], $redirect['status_code']);
        }

        return $next($request);
    }

    protected function getRedirects(): array
    {
        if (! config('redirects.cache.enabled', true)) {
            return $this->loadRedirects();
        }

        return Cache::remember(
            config('redirects.cache.key', 'redirects_cache'),
            config('redirects.cache.ttl', 86400),
            fn () => $this->loadRedirects()
        );
    }

    protected function loadRedirects(): array
    {
        return Redirect::all()
            ->keyBy('old_url')
            ->map(fn ($redirect) => [
                'id' => $redirect->id,
                'new_url' => $redirect->new_url,
                'status_code' => $redirect->status_code,
            ])
            ->toArray();
    }

    protected function trackHit(int $redirectId): void
    {
        // Use a raw query to avoid loading the model and clearing cache
        // This is more performant and doesn't trigger cache clearing
        $tableName = config('redirects.table_name', 'redirects');
        \DB::table($tableName)
            ->where('id', $redirectId)
            ->update([
                'hit_count' => \DB::raw('hit_count + 1'),
                'last_hit_at' => now(),
            ]);
    }
}
