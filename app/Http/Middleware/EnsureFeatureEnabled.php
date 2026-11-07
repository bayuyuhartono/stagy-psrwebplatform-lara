<?php

namespace App\Http\Middleware;

use App\Models\FeatureFlag;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $isEnabled = FeatureFlag::query()
            ->where('name', $feature)
            ->where('enabled', true)
            ->exists();

        abort_unless($isEnabled, 404);

        return $next($request);
    }
}
