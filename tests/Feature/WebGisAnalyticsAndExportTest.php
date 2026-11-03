<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class WebGisAnalyticsAndExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge();

        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->string('password')->nullable();
                $table->string('role')->default('dinas');
                $table->boolean('aktif')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    /**
     * 1. Guest visiting / and /portal renders Public/Portal Inertia component.
     */
    public function test_guest_visiting_home_and_portal_renders_public_portal(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Portal', false));

        $this->get('/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Portal', false));
    }

    /**
     * 2. Authenticated Admin visiting / and /portal redirects to /internal/admin.
     */
    public function test_authenticated_admin_visiting_home_or_portal_redirects_to_internal_admin(): void
    {
        $admin = new User([
            'id' => 1,
            'name' => 'Super Administrator',
            'email' => 'admin.diskanak@banyumaskab.go.id',
            'role' => 'admin',
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->get('/')
            ->assertStatus(302)
            ->assertRedirect(route('internal.admin'));

        $this->actingAs($admin)
            ->get('/portal')
            ->assertStatus(302)
            ->assertRedirect(route('internal.admin'));
    }

    /**
     * 3. Authenticated Dinas / Stakeholder visiting / and /portal redirects to /internal/dinas.
     */
    public function test_authenticated_stakeholder_and_dinas_visiting_home_or_portal_redirects_to_internal_dinas(): void
    {
        $stakeholder = new User([
            'id' => 2,
            'name' => 'drh. Sulistyo',
            'email' => 'drh.sulistyo@banyumaskab.go.id',
            'role' => 'stakeholder',
            'aktif' => true,
        ]);

        $this->actingAs($stakeholder)
            ->get('/')
            ->assertStatus(302)
            ->assertRedirect(route('internal.dinas'));

        $dinas = new User([
            'id' => 3,
            'name' => 'Petugas Lapangan',
            'email' => 'petugas@banyumaskab.go.id',
            'role' => 'dinas',
            'aktif' => true,
        ]);

        $this->actingAs($dinas)
            ->get('/portal')
            ->assertStatus(302)
            ->assertRedirect(route('internal.dinas'));
    }

    /**
     * 4. Authenticated User with ?preview=1 bypasses role redirect and renders Public/Portal.
     */
    public function test_authenticated_user_with_preview_param_bypasses_redirect(): void
    {
        $admin = new User([
            'id' => 1,
            'name' => 'Super Administrator',
            'email' => 'admin.diskanak@banyumaskab.go.id',
            'role' => 'admin',
            'aktif' => true,
        ]);

        // Preview query on root
        $this->actingAs($admin)
            ->get('/?preview=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Portal', false));

        $stakeholder = new User([
            'id' => 2,
            'name' => 'drh. Sulistyo',
            'email' => 'drh.sulistyo@banyumaskab.go.id',
            'role' => 'stakeholder',
            'aktif' => true,
        ]);

        // Preview query on /portal
        $this->actingAs($stakeholder)
            ->get('/portal?preview=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Portal', false));
    }

    /**
     * 5. Invalid login attempt fails with validation error and does not authenticate.
     */
    public function test_invalid_login_attempt_fails_with_validation_error(): void
    {
        $response = $this->post('/internal/login', [
            'email' => 'nonexistent@banyumaskab.go.id',
            'password' => 'wrong-password',
            'role' => 'admin',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Auth::check());
    }

    /**
     * 6. Rate limiting on /internal/login blocks requests after 10 attempts in 1 minute with HTTP 429.
     */
    public function test_internal_login_rate_limiting_throttles_after_limit(): void
    {
        // 10 attempts consume the limit
        for ($i = 0; $i < 10; $i++) {
            $this->post('/internal/login', [
                'email' => 'throttletest@banyumaskab.go.id',
                'password' => 'invalid-attempt',
                'role' => 'admin',
            ]);
        }

        // 11th attempt must trigger 429 Too Many Requests
        $response = $this->post('/internal/login', [
            'email' => 'throttletest@banyumaskab.go.id',
            'password' => 'invalid-attempt',
            'role' => 'admin',
        ]);

        $response->assertStatus(429);
    }
}
