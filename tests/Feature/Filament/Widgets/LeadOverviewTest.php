<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\LeadOverview;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_dashboard_counts_only_leads_assigned_to_the_signed_in_user(): void
    {
        $this->createRoles();
        $sale = User::factory()->create();
        $sale->assignRole('sale');
        $otherSale = User::factory()->create();
        $otherSale->assignRole('sale');

        Lead::create([
            'request_type' => 'quote',
            'full_name' => 'Assigned lead',
            'phone' => '0900000001',
            'status' => 'new',
            'assigned_to' => $sale->id,
        ]);
        Lead::create([
            'request_type' => 'quote',
            'full_name' => 'Other salesperson lead',
            'phone' => '0900000002',
            'status' => 'sold',
            'assigned_to' => $otherSale->id,
        ]);

        Livewire::actingAs($sale)
            ->test(LeadOverview::class)
            ->assertSee('Tổng lead')
            ->assertSee('Mới')
            ->assertSee('1')
            ->assertSee('Đã bán')
            ->assertSee('0');
    }

    public function test_admin_dashboard_counts_leads_from_all_sales_assignments(): void
    {
        $this->createRoles();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $sale = User::factory()->create();
        $sale->assignRole('sale');

        Lead::create([
            'request_type' => 'quote',
            'full_name' => 'Assigned lead',
            'phone' => '0900000001',
            'status' => 'new',
            'assigned_to' => $sale->id,
        ]);
        Lead::create([
            'request_type' => 'quote',
            'full_name' => 'Unassigned lead',
            'phone' => '0900000002',
            'status' => 'sold',
        ]);

        Livewire::actingAs($admin)
            ->test(LeadOverview::class)
            ->assertSee('Tổng lead')
            ->assertSee('2')
            ->assertSee('Đã bán');
    }

    public function test_dashboard_hides_lead_overview_from_marketing_users(): void
    {
        $this->createRoles();
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');

        $this->actingAs($marketing);

        $this->assertFalse(LeadOverview::canView());
    }

    private function createRoles(): void
    {
        foreach (['admin', 'marketing', 'sale'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }
}
