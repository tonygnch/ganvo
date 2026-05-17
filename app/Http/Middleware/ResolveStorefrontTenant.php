<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStorefrontTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomain = config('ganvo.central_domain');

        $tenant = $this->resolveByCustomDomain($host) ?? $this->resolveBySubdomain($host, $centralDomain);

        if (! $tenant || ! $tenant->store?->is_live || $tenant->status !== Tenant::STATUS_ACTIVE) {
            abort(404);
        }

        app()->instance('current_tenant', $tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }

    private function resolveByCustomDomain(string $host): ?Tenant
    {
        $store = Store::query()
            ->where('custom_domain', $host)
            ->whereNotNull('custom_domain_verified_at')
            ->with('tenant')
            ->first();

        return $store?->tenant;
    }

    private function resolveBySubdomain(string $host, string $centralDomain): ?Tenant
    {
        if ($host === $centralDomain || ! str_ends_with($host, '.' . $centralDomain)) {
            return null;
        }

        $slug = str_replace('.' . $centralDomain, '', $host);

        return Tenant::where('slug', $slug)
            ->with('store')
            ->first();
    }
}
