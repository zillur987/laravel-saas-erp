<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles must exist before assignRole() is called during registration.
        foreach (['owner', 'admin', 'member'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    /** @test */
    public function registering_creates_a_tenant_and_assigns_owner_role(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Acme Corp',
            'name' => 'Jane Doe',
            'email' => 'jane@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertCount(1, Tenant::all());
        $this->assertCount(1, User::withoutGlobalScopes()->get());

        $tenant = Tenant::first();
        $this->assertEquals('acme-corp', $tenant->subdomain);

        app()->instance('tenant', $tenant);
        $user = User::first();

        $this->assertTrue($user->hasRole('owner'));
        $this->assertEquals($tenant->id, $user->tenant_id);
    }

    /** @test */
    public function duplicate_company_names_get_unique_subdomains(): void
    {
        $this->post('/register', [
            'company_name' => 'Acme Corp',
            'name' => 'Jane Doe',
            'email' => 'jane@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->post('/register', [
            'company_name' => 'Acme Corp',
            'name' => 'John Smith',
            'email' => 'john@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $subdomains = Tenant::pluck('subdomain')->toArray();

        $this->assertCount(2, $subdomains);
        $this->assertContains('acme-corp', $subdomains);
        $this->assertContains('acme-corp-2', $subdomains);
    }
}