<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $userData = null;

        if ($user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->nama ?? $user->name,
                'nama' => $user->nama ?? $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [$user->role],
                'permissions' => method_exists($user, 'getPermissionSlugs') ? $user->getPermissionSlugs() : [],
                'is_admin' => in_array($user->role, ['superadmin', 'admin_dinas', 'admin'], true),
                'is_superadmin' => ($user->role === 'superadmin'),
                'is_stakeholder' => $user->role === 'stakeholder',
            ];
        } elseif ($request->hasSession() && $request->session()->has('authenticated_role')) {
            $sessionRole = (string) $request->session()->get('authenticated_role');
            $sessionUserId = (int) $request->session()->get('authenticated_user_id', $sessionRole === 'admin' ? 1 : 2);
            $sessionName = (string) $request->session()->get('authenticated_name', $sessionRole === 'admin' ? 'Satrio Wibowo' : 'drh. Sulistyo');
            $sessionEmail = (string) $request->session()->get('authenticated_email', $sessionRole === 'admin' ? 'admin.diskanak@banyumaskab.go.id' : 'drh.sulistyo@banyumaskab.go.id');

            $userData = [
                'id' => $sessionUserId,
                'name' => $sessionName,
                'nama' => $sessionName,
                'email' => $sessionEmail,
                'role' => $sessionRole,
                'roles' => [$sessionRole],
                'permissions' => [],
                'is_admin' => in_array($sessionRole, ['superadmin', 'admin_dinas', 'admin'], true),
                'is_superadmin' => ($sessionRole === 'superadmin'),
                'is_stakeholder' => $sessionRole === 'stakeholder',
            ];
        }

        return array_merge(parent::share($request), [
            'name' => config('app.name', 'TERNAKVERSE WebGIS Banyumas'),
            'appName' => config('app.name', 'TERNAKVERSE WebGIS Banyumas'),
            'auth' => [
                'user' => $userData,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ]);
    }
}
