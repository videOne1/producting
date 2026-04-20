<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantExtract
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->tenant_id === null) {
            return response()->json([
                'message' => 'Authenticated user is not linked to a tenant.',
            ], 403);
        }

        $headerTenantId = $request->header('X-Tenant-Id');

        if ($headerTenantId === null || $headerTenantId === '') {
            $tenantId = (int) $user->tenant_id;
        } elseif (! ctype_digit((string) $headerTenantId)) {
            return response()->json([
                'message' => 'X-Tenant-Id header must be a valid integer.',
            ], 400);
        } else {
            $tenantId = (int) $headerTenantId;
        }

        if ($tenantId !== (int) $user->tenant_id) {
            return response()->json([
                'message' => 'You are not allowed to access this tenant.',
            ], 403);
        }

        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }
}
