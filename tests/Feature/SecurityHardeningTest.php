<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalAuditMiddleware;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin+'.uniqid().'@example.com',
            'password' => bcrypt('password123!A'),
            'role' => 'admin',
            'must_change_password' => false,
        ]);
    }

    public function test_meeting_pdf_route_is_not_public_anymore(): void
    {
        $response = $this->getJson('/api/meetings/1/pdf');
        $response->assertStatus(401);
    }

    public function test_generate_access_returns_activation_link_not_plain_password(): void
    {
        $operator = User::create([
            'name' => 'Operator',
            'email' => 'operator+'.uniqid().'@example.com',
            'password' => bcrypt('password123!A'),
            'role' => 'viewer',
            'must_change_password' => false,
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'operator-members'],
            ['type' => 'system', 'description' => 'Operator role for member access generation']
        );

        $permission = Permission::firstOrCreate(
            ['name' => 'members.generate-access'],
            [
                'group' => 'members',
                'description' => 'Generate member portal access',
                'method' => 'POST',
            ]
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $operator->roles()->syncWithoutDetaching([$role->id]);

        $member = Member::create([
            'name' => 'Membro Teste',
            'email' => 'membro+'.uniqid().'@example.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($operator)
            ->postJson('/api/members/'.$member->id.'/generate-access');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'activation' => ['email', 'activation_url', 'expires_in_minutes'],
            ])
            ->assertJsonMissingPath('credentials.password');
    }

    public function test_password_forgot_endpoint_returns_generic_message(): void
    {
        $response = $this->postJson('/api/password/forgot', [
            'email' => 'naoexiste@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);
    }

    public function test_global_audit_middleware_uses_safe_whitelist_payload(): void
    {
        $middleware = new GlobalAuditMiddleware();

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('buildSafeAuditPayload');
        $method->setAccessible(true);

        $request = request()->create('/api/test', 'POST', [
            'cpf' => '12345678901',
            'email' => 'secret@example.com',
            'description' => str_repeat('x', 300),
            'amount' => 10,
            'unexpected' => 'must-not-be-logged',
        ]);

        $safe = $method->invoke($middleware, $request);

        $this->assertArrayHasKey('payload', $safe);
        $this->assertArrayHasKey('payload_keys', $safe);
        $this->assertArrayHasKey('amount', $safe['payload']);
        $this->assertArrayNotHasKey('unexpected', $safe['payload']);
        $this->assertArrayNotHasKey('cpf', $safe['payload']);
        $this->assertArrayNotHasKey('email', $safe['payload']);
        $this->assertLessThanOrEqual(203, strlen($safe['payload']['description']));
    }

    public function test_mfa_enrollment_is_required_for_critical_profiles(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->getJson('/api/categories');

        $response->assertStatus(403)
            ->assertJson([
                'code' => 'MFA_ENROLL_REQUIRED',
            ]);
    }

    public function test_mfa_verify_accepts_backup_code_and_consumes_it(): void
    {
        $admin = $this->adminUser();

        $plainBackupCode = 'ABCD1234';
        $admin->update([
            'mfa_enabled' => true,
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_backup_codes' => [Hash::make($plainBackupCode)],
            'mfa_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->postJson('/api/mfa/verify', [
            'code' => $plainBackupCode,
        ]);

        $response->assertOk()
            ->assertJson([
                'backup_codes_left' => 0,
            ]);

        $admin->refresh();
        $this->assertSame([], $admin->mfa_backup_codes ?? []);
    }

    public function test_data_purge_anonymizes_old_audits_and_removes_old_request_logs(): void
    {
        $oldAudit = AuditLog::create([
            'user_id' => null,
            'auditable_type' => 'Tests\\AuditEntity',
            'auditable_id' => 1,
            'action' => 'update',
            'old_values' => ['email' => 'old@example.com'],
            'new_values' => [
                'cpf' => '12345678901',
                'phone' => '(41) 99999-9999',
                'notes' => 'Contato old@example.com',
            ],
            'url' => 'https://example.test/api/members/1',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'tags' => null,
        ]);
        $oldAudit->forceFill(['created_at' => now()->subDays(200)])->saveQuietly();

        $oldRequestLog = AuditLog::create([
            'user_id' => null,
            'auditable_type' => 'SystemRequest',
            'auditable_id' => 0,
            'action' => 'post',
            'new_values' => ['payload' => ['amount' => 10]],
            'url' => 'https://example.test/api/test',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'tags' => ['request_log' => true],
        ]);
        $oldRequestLog->forceFill(['created_at' => now()->subDays(100)])->saveQuietly();

        $this->artisan('data:purge')->assertExitCode(0);

        $oldAudit->refresh();
        $this->assertNull($oldAudit->ip_address);
        $this->assertNull($oldAudit->user_agent);
        $this->assertSame('[ANONYMIZED]', $oldAudit->new_values['cpf']);
        $this->assertSame('[ANONYMIZED]', $oldAudit->new_values['phone']);
        $this->assertStringContainsString('[ANONYMIZED_EMAIL]', $oldAudit->new_values['notes']);

        $this->assertDatabaseMissing('audit_logs', [
            'id' => $oldRequestLog->id,
        ]);
    }
}
