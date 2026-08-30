<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_tenant_cannot_see_another_tenants_users(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A', 'subdomain' => 'tenanta', 'plan' => 'A']);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'subdomain' => 'tenantb', 'plan' => 'B']);

        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'User A',
            'email' => 'a@tenanta.com',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'User B',
            'email' => 'b@tenantb.com',
            'password' => bcrypt('password'),
        ]);

        // Simulate resolving Tenant A (this is what IdentifyTenant does per-request).
        app()->instance('tenant', $tenantA);

        $visibleUsers = User::all();

        $this->assertCount(1, $visibleUsers);
        $this->assertEquals($userA->id, $visibleUsers->first()->id);
    }
}