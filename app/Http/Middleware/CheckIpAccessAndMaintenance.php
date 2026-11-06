<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SystemGovernanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIpAccessAndMaintenance
{
    public function __construct(
        protected SystemGovernanceService $governanceService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = (string) ($request->ip() ?? '127.0.0.1');

        // 1. Periksa kebijakan firewall akses IP
        if (! $this->governanceService->isIpAllowed($clientIp)) {
            if ($request->expectsJson() || $request->is('admin/*', 'internal/*', 'api/*')) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'IP_ACCESS_DENIED',
                    'message' => "Akses ditolak: Alamat IP ({$clientIp}) tidak diizinkan oleh kebijakan firewall sistem.",
                ], Response::HTTP_FORBIDDEN);
            }

            abort(Response::HTTP_FORBIDDEN, "Akses ditolak: Alamat IP ({$clientIp}) tidak diizinkan.");
        }

        // 2. Periksa status mode pemeliharaan (Maintenance Mode)
        if ($this->governanceService->isMaintenanceActive()) {
            $user = $request->user();
            $isSuperAdmin = $user && (
                $user->role === 'superadmin' ||
                (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            );

            // Rute bypass pemeliharaan (Super Admin, autentikasi darurat, dan state toggle)
            $isBypass = $request->is(
                'up',
                'login',
                'logout',
                'internal/login',
                'internal/logout',
                'admin/api/governance/state',
                'admin/api/governance/maintenance'
            );

            if (! $isSuperAdmin && ! $isBypass) {
                $message = (string) $this->governanceService->get(
                    'maintenance.message',
                    'Sistem TernakVerse sedang dalam masa pemeliharaan terencana. Silakan coba beberapa saat lagi.'
                );
                $scheduledEnd = $this->governanceService->get('maintenance.scheduled_end', null);

                if ($request->expectsJson() || $request->is('admin/*', 'internal/*', 'api/*')) {
                    return response()->json([
                        'status' => 'maintenance',
                        'error_code' => 'SYSTEM_MAINTENANCE',
                        'message' => $message,
                        'scheduled_end' => $scheduledEnd,
                    ], Response::HTTP_SERVICE_UNAVAILABLE);
                }

                abort(Response::HTTP_SERVICE_UNAVAILABLE, $message);
            }
        }

        return $next($request);
    }
}
