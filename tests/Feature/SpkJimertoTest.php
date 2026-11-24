<?php

namespace Tests\Feature;

use App\Models\CalculationRun;
use App\Models\Criterion;
use App\Models\MonthlySalesRecap;
use App\Models\Product;
use App\Models\User;
use App\Services\AhpService;
use App\Services\SalesRecapSyncService;
use App\Services\SawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkJimertoTest extends TestCase
{
    public function test_authentication_and_quick_login(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        $response = $this->get('/quick-login/admin');
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_dashboard_accessible_and_renders_kpis(): void
    {
        $admin = User::where('role', 'admin')->first();
        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Camilan Jimerto');
        $response->assertSee('Rengginang'); // Rank #1 product
        $response->assertSee('Tingkat Permintaan');
    }

    public function test_ahp_consistency_and_weights_calculation(): void
    {
        $criteria = Criterion::orderBy('id')->get();
        $ahpService = app(AhpService::class);

        // Standard pairs that match target weights [0.30, 0.20, 0.20, 0.15, 0.15]
        $pairwiseMap = [
            "{$criteria[0]->id}_{$criteria[1]->id}" => 1.5,
            "{$criteria[0]->id}_{$criteria[2]->id}" => 1.5,
            "{$criteria[0]->id}_{$criteria[3]->id}" => 2.0,
            "{$criteria[0]->id}_{$criteria[4]->id}" => 2.0,
            "{$criteria[1]->id}_{$criteria[2]->id}" => 1.0,
            "{$criteria[1]->id}_{$criteria[3]->id}" => 1.3333,
            "{$criteria[1]->id}_{$criteria[4]->id}" => 1.3333,
            "{$criteria[2]->id}_{$criteria[3]->id}" => 1.3333,
            "{$criteria[2]->id}_{$criteria[4]->id}" => 1.3333,
            "{$criteria[3]->id}_{$criteria[4]->id}" => 1.0,
        ];

        $result = $ahpService->calculate($pairwiseMap, $criteria);
        $this->assertTrue($result['is_consistent']);
        $this->assertLessThanOrEqual(0.10, $result['consistency_ratio']);
        $this->assertEquals(0.30, round($result['weights'][$criteria[0]->id], 2));
    }

    public function test_drilldown_api_returns_detailed_breakdown(): void
    {
        $admin = User::where('role', 'admin')->first();
        $rengginang = Product::where('code', 'A4')->first();

        $response = $this->actingAs($admin)->getJson("/api/drilldown/{$rengginang->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'product' => ['id', 'code', 'name'],
            'ranking' => ['rank', 'final_score', 'priority_tier', 'narrative'],
            'breakdown',
            'radar_labels',
            'radar_values',
            'monthly_sales',
            'evaluation_period',
        ]);
        $this->assertEquals(1, $response->json('ranking.rank'));
    }

    public function test_dynamic_sales_recap_sync_updates_c1_and_saw(): void
    {
        $run = CalculationRun::where('is_active_dashboard', true)->first();
        $rengginang = Product::where('code', 'A4')->first();

        // Increment sales
        $recap = MonthlySalesRecap::where('product_id', $rengginang->id)->where('year', 2026)->where('month', 10)->first();
        $initialUnits = $recap->total_units_sold;
        $recap->update(['total_units_sold' => $initialUnits + 50]);

        $syncService = app(SalesRecapSyncService::class);
        $syncResult = $syncService->syncRecapToAssessments($run);

        $this->assertGreaterThan(0, $syncResult['total_units_aggregated']);
        $this->assertEquals('Rengginang', $syncResult['top_product']);
    }

    public function test_what_if_sensitivity_simulation_endpoint(): void
    {
        $admin = User::where('role', 'admin')->first();
        $run = CalculationRun::where('is_active_dashboard', true)->first();

        $response = $this->actingAs($admin)->postJson('/api/simulate', [
            'run_id' => $run->id,
            'weights' => [
                '1' => 10,
                '2' => 10,
                '3' => 10,
                '4' => 10,
                '5' => 60,
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $this->assertCount(30, $response->json('rankings'));
    }

    public function test_rbac_admin_routes_forbidden_for_pemilik_umkm(): void
    {
        $owner = User::where('role', 'pemilik_umkm')->first();

        // Owner trying to access admin-only monthly recap input
        $response = $this->actingAs($owner)->get('/sales-recaps');
        $response->assertStatus(403);

        // Owner can access AHP questionnaire
        $responseAhp = $this->actingAs($owner)->get('/ahp');
        $responseAhp->assertStatus(200);
    }
}

