<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     * Mengatur otorisasi RBAC (Role-Based Access Control):
     * - Role 'stakeholder': Hanya memiliki izin membaca data (Read-Only: GET / HEAD).
     * - Role 'admin': Memiliki izin penuh (Full CRUD).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$allowedRoles
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        // 1. Resolve pengguna aktif (Auth Session, Bearer Token, atau X-User-Id Header di dev/test)
        $user = null;
        try {
            $user = $request->user();
        } catch (\Throwable $e) {
            $user = null;
        }

        if (!$user && $request->hasSession() && $request->session()->has('authenticated_role')) {
            $sessionRole = (string) $request->session()->get('authenticated_role');
            $user = new User([
                'id' => (int) $request->session()->get('authenticated_user_id', $sessionRole === 'admin' ? 1 : 2),
                'nama' => (string) $request->session()->get('authenticated_name', $sessionRole === 'admin' ? 'Super Administrator' : 'Petugas Dinas'),
                'name' => (string) $request->session()->get('authenticated_name', $sessionRole === 'admin' ? 'Super Administrator' : 'Petugas Dinas'),
                'email' => (string) $request->session()->get('authenticated_email', $sessionRole . '@banyumaskab.go.id'),
                'role' => $sessionRole,
                'aktif' => true,
            ]);
            auth()->setUser($user);
            $request->setUserResolver(fn () => $user);
        }

        if (!$user && app()->environment('local', 'testing')) {
            $headerUserId = $request->header('X-User-Id');
            $headerRole = $request->header('X-User-Role') ?? $request->query('role');

            if ($headerUserId) {
                try {
                    $user = User::find($headerUserId);
                } catch (\Throwable $e) {
                    $user = null;
                }
            } elseif ($headerRole) {
                try {
                    $user = User::where('role', strtolower((string) $headerRole))->first();
                } catch (\Throwable $e) {
                    $user = null;
                }

                if (!$user) {
                    $roleSlug = strtolower((string) $headerRole) === 'admin' ? 'admin' : 'stakeholder';
                    $user = new User([
                        'id' => $roleSlug === 'admin' ? 1 : 2,
                        'nama' => $roleSlug === 'admin' ? 'Super Administrator' : 'drh. Sulistyo (Petugas Dinas)',
                        'name' => $roleSlug === 'admin' ? 'Super Administrator' : 'drh. Sulistyo (Petugas Dinas)',
                        'email' => $roleSlug . '@banyumaskab.go.id',
                        'role' => $roleSlug,
                        'aktif' => true,
                    ]);
                }
            }
        }

        if ($user) {
            auth()->setUser($user);
            $request->setUserResolver(fn () => $user);
        }

        // Jika pengguna tidak terautentikasi
        if (!$user) {
            // Untuk web request (SPA shell routes atau browser biasa tanpa ekspektasi JSON), alihkan ke login
            if (!$request->expectsJson() && $request->is('internal/admin', 'internal/dinas', 'admin/panel', 'admin/audit-logs')) {
                return redirect()->route('internal.login');
            }

            if ($request->expectsJson() || $request->is('internal/*', 'admin/*', 'peta/*', 'analitik/*', 'laporan/*')) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'UNAUTHENTICATED',
                    'message' => 'Sesi autentikasi tidak ditemukan. Silakan masuk terlebih dahulu.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->route('internal.login');
        }

        $role = strtolower($user->role);

        // 2. Jika aturan peran spesifik ditentukan pada rute
        if (!empty($allowedRoles)) {
            $allowed = array_map('strtolower', $allowedRoles);
            if (!in_array($role, $allowed, true)) {
                if (!$request->expectsJson() && $request->is('internal/admin', 'internal/dinas', 'admin/panel', 'admin/audit-logs')) {
                    abort(403, "Akses Ditolak: Peran '{$role}' tidak memiliki izin untuk mengakses rute ini.");
                }

                if ($request->expectsJson() || $request->is('internal/*', 'admin/*', 'peta/*', 'analitik/*', 'laporan/*')) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'RBAC_FORBIDDEN',
                        'message' => "Akses Ditolak: Peran pengguna '{$role}' tidak memiliki izin untuk mengakses rute ini.",
                    ], Response::HTTP_FORBIDDEN);
                }

                abort(403, "Akses Ditolak: Peran '{$role}' tidak memiliki izin untuk mengakses rute ini.");
            }
        }

        // 3. Kebijakan Read-Only Mutlak untuk Role 'stakeholder'
        if ($role === 'stakeholder') {
            $safeMethods = ['GET', 'HEAD', 'OPTIONS'];
            if (!in_array($request->method(), $safeMethods, true)) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'RBAC_READ_ONLY',
                    'message' => "Akses Ditolak: Akun Pimpinan Dinas (Stakeholder) hanya memiliki hak akses Read-Only. Tindakan penambahan, pengubahan, atau penghapusan data hanya dapat dilakukan oleh Administrator Geospasial.",
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
