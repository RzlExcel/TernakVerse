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
        // 1. Resolve pengguna aktif (Auth Session, Bearer Token, atau X-User-Id Header)
        $user = $request->user();

        if (!$user) {
            $headerUserId = $request->header('X-User-Id');
            $headerRole = $request->header('X-User-Role') ?? $request->query('role');

            if ($headerUserId) {
                $user = User::find($headerUserId);
            } elseif ($headerRole) {
                $user = User::where('role', strtolower($headerRole))->first();
            } else {
                // Default fallback untuk demo / sesi aktif: ambil user stakeholder pertama
                $user = User::where('role', 'stakeholder')->first();
            }
        }

        if ($user) {
            auth()->setUser($user);
            $request->setUserResolver(fn () => $user);
        }

        // Default role jika tamu mengakses portal internal
        $role = $user ? strtolower($user->role) : 'stakeholder';

        // 2. Jika aturan peran spesifik ditentukan pada rute
        if (!empty($allowedRoles)) {
            $allowed = array_map('strtolower', $allowedRoles);
            if (!in_array($role, $allowed)) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'RBAC_FORBIDDEN',
                    'message' => "Akses Ditolak: Peran pengguna '{$role}' tidak memiliki izin untuk mengakses rute ini.",
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // 3. Kebijakan Read-Only Mutlak untuk Role 'stakeholder'
        if ($role === 'stakeholder') {
            $safeMethods = ['GET', 'HEAD', 'OPTIONS'];
            if (!in_array($request->method(), $safeMethods)) {
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
