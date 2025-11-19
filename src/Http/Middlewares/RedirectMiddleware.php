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
                'new_url' => $redirect->new_url,
                'status_code' => $redirect->status_code,
            ])
            ->toArray();
    }
}
