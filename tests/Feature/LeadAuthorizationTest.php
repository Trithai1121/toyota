<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_users_can_only_view_and_update_assigned_leads(): void
    {
        $this->createRoles();
        $sale = User::factory()->create();
        $sale->assignRole('sale');
        $otherSale = User::factory()->create();
        $otherSale->assignRole('sale');
        $lead = Lead::create([
            'request_type' => 'quote',
            'full_name' => 'Nguyen Van A',
            'phone' => '0900000000',
            'status' => 'new',
        ]);

        $this->assertFalse(Gate::forUser($sale)->allows('view', $lead));
        $this->assertFalse(Gate::forUser($sale)->allows('update', $lead));

        $lead->update(['assigned_to' => $sale->id]);

        $this->assertTrue(Gate::forUser($sale)->allows('view', $lead));
        $this->assertTrue(Gate::forUser($sale)->allows('update', $lead));
        $this->assertFalse(Gate::forUser($otherSale)->allows('view', $lead));
    }

    public function test_marketing_can_manage_content_but_cannot_access_leads_or_admin_settings(): void
    {
        $this->createRoles();
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');

        $this->assertTrue(Gate::forUser($marketing)->allows('viewAny', Post::class));
        $this->assertFalse(Gate::forUser($marketing)->allows('viewAny', Lead::class));
        $this->assertFalse(Gate::forUser($marketing)->allows('viewAny', Setting::class));
    }

    public function test_sales_can_only_view_notes_for_assigned_leads_and_cannot_export_leads(): void
    {
        $this->createRoles();
        $sale = User::factory()->create();
        $sale->assignRole('sale');
        $lead = Lead::create([
            'request_type' => 'quote',
            'full_name' => 'Nguyen Van A',
            'phone' => '0900000000',
            'status' => 'new',
        ]);
        $note = LeadNote::create([
            'lead_id' => $lead->id,
            'user_id' => $sale->id,
            'body' => 'Test note',
        ]);

        $this->assertFalse(Gate::forUser($sale)->allows('view', $note));
        $this->assertFalse(Gate::forUser($sale)->allows('export', Lead::class));

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue(Gate::forUser($admin)->allows('export', Lead::class));

        $lead->update(['assigned_to' => $sale->id]);

        $this->assertTrue(Gate::forUser($sale)->allows('view', $note));
        $this->assertFalse(Gate::forUser($sale)->allows('update', $note));
        $this->assertFalse(Gate::forUser($sale)->allows('delete', $note));
    }

    public function test_inactive_users_cannot_access_the_admin_panel(): void
    {
        $this->createRoles();
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('admin');

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_active_admin_multi_factor_setup_follows_panel_configuration(): void
    {
        $this->createRoles();
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));

        $response = $this->actingAs($user)->get('/admin');

        if (config('auth.admin_mfa_required')) {
            $response->assertRedirect('/admin/multi-factor-authentication/set-up');

            return;
        }

        $response->assertOk();
    }

    private function createRoles(): void
    {
        foreach (['admin', 'marketing', 'sale'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }
}
