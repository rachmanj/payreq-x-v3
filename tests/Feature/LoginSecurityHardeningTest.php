<?php

namespace Tests\Feature;

use App\Models\LoginAudit;
use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function createActiveUser(string $username = 'audituser', string $password = 'secret-pass'): User
    {
        return User::factory()->create([
            'username' => $username,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);
    }

    protected function postLogin(string $username, string $password = 'wrong')
    {
        return $this->post(route('authenticate'), [
            'username' => $username,
            'password' => $password,
        ]);
    }

    #[Test]
    public function sixth_failed_login_is_throttled_with_indonesian_message(): void
    {
        $this->createActiveUser('throttle-test');

        for ($i = 0; $i < 5; $i++) {
            $this->postLogin('throttle-test')->assertRedirect();
        }

        $response = $this->postLogin('throttle-test');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            session('errors')->first('username')
        );
    }

    #[Test]
    public function successful_login_clears_rate_limiter_for_username_and_ip(): void
    {
        $password = 'correct-horse';
        $this->createActiveUser('reset-throttle', $password);

        for ($i = 0; $i < 4; $i++) {
            $this->postLogin('reset-throttle')->assertRedirect();
        }

        $this->post(route('authenticate'), [
            'username' => 'reset-throttle',
            'password' => $password,
        ])->assertRedirect(route('dashboard.index'));

        for ($i = 0; $i < 5; $i++) {
            $this->postLogin('reset-throttle')->assertRedirect();
        }

        $this->postLogin('reset-throttle')->assertRedirect(route('login'));
    }

    #[Test]
    public function get_login_is_not_throttled_by_login_limiter(): void
    {
        $this->createActiveUser('get-login');

        for ($i = 0; $i < 6; $i++) {
            $this->postLogin('get-login');
        }

        $this->get(route('login'))->assertOk();
    }

    #[Test]
    public function untrusted_remote_address_ignores_spoofed_x_forwarded_for(): void
    {
        Request::setTrustedProxies(
            config('trustedproxy.proxies'),
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $request = Request::create('http://example.com/login', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.50',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
        ]);

        $this->assertSame('203.0.113.50', $request->getClientIp());
    }

    #[Test]
    public function trusted_local_proxy_uses_x_forwarded_for_client_ip(): void
    {
        Request::setTrustedProxies(
            config('trustedproxy.proxies'),
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $request = Request::create('http://example.com/login', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.55',
        ]);

        $this->assertSame('203.0.113.55', $request->getClientIp());
    }

    #[Test]
    public function failed_and_successful_logins_are_audited_and_last_login_is_updated(): void
    {
        $password = 'audit-pass-123';
        $user = $this->createActiveUser('audit-me', $password);

        $this->postLogin('audit-me')->assertRedirect();

        $this->assertDatabaseHas('login_audits', [
            'username_dicoba' => 'audit-me',
            'berhasil' => false,
            'user_id' => $user->id,
        ]);

        $this->post(route('authenticate'), [
            'username' => 'audit-me',
            'password' => $password,
        ])->assertRedirect(route('dashboard.index'));

        $this->assertDatabaseHas('login_audits', [
            'username_dicoba' => 'audit-me',
            'berhasil' => true,
            'user_id' => $user->id,
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
        $this->assertSame(2, LoginAudit::query()->where('username_dicoba', 'audit-me')->count());
    }

    #[Test]
    public function login_page_includes_security_headers_and_hsts_only_on_https(): void
    {
        $http = $this->get(route('login'));
        $http->assertOk();
        $http->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $http->assertHeader('X-Content-Type-Options', 'nosniff');
        $http->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertFalse($http->headers->has('Strict-Transport-Security'));

        $https = $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get(route('login'));
        $https->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    #[Test]
    public function superadmin_with_permission_can_view_login_audit_page(): void
    {
        Permission::firstOrCreate(['name' => 'view_login_audit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'akses_admin', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $role->givePermissionTo(['view_login_audit', 'akses_admin']);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('superadmin');

        LoginAudit::query()->create([
            'username_dicoba' => 'someone',
            'berhasil' => false,
            'ip' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.login-audits.index'))
            ->assertOk()
            ->assertSee('someone')
            ->assertSee('Gagal');
    }

    #[Test]
    public function login_throttle_key_uses_username_and_ip(): void
    {
        $request = Request::create('/login', 'POST', [
            'username' => 'TestUser',
        ], [], [], ['REMOTE_ADDR' => '192.168.1.10']);

        $this->assertSame('testuser|192.168.1.10', LoginThrottle::signatureKey($request));
    }
}
