<?php

namespace SmartCms\Redirects\Http\Middlewares;

use Closure;
use SmartCms\Redirects\Models\Redirect;

class RedirectMiddleware
{
    public function handle($request, Closure $next)
    {
        $oldPath = request()->path();
        if (!str_starts_with($oldPath, '/')) {
            $oldPath = '/' . $oldPath;
        }
        $redirect = Redirect::where('old_url', $oldPath)->first();
        if ($redirect) {
            return redirect($redirect->new_url, $redirect->status_code);
        }
        return $next($request);
    }
}
