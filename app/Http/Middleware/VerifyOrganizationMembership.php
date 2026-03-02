<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOrganizationMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if (!$organization instanceof Organization) {
            abort(404);
        }

        $user = $request->user();

        // Allow super admins and members of the organization
        if ($user->isSuperAdmin() || $user->belongsToOrganization($organization)) {
            return $next($request);
        }

        abort(403, 'Unauthorized to access this organization.');
    }
}
