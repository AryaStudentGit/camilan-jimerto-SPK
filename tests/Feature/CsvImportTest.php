<?php

namespace Tests\Feature;

use App\Models\CalculationRun;
use App\Models\MonthlySalesRecap;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    public function test_can_download_csv_template()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('sales-recaps.template', ['year' => 2026, 'month' => 10]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=template_rekap_penjualan_jimerto_2026_10.csv');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('kode_produk,nama_produk,tahun,bulan,total_unit_terjual', $content);
        $this->assertStringContainsString('A1,Rambak', $content);
        $this->assertStringContainsString('A30,Keciput', $content);
    }

    public function test_can_import_csv_and_sync_dashboard()
    {
        $admin = User::where('role', 'admin')->first();
        $activeRun = CalculationRun::where('is_active_dashboard', true)->first();

        // Sample CSV content
        $csvContent = "kode_produk,nama_produk,tahun,bulan,total_unit_terjual,total_omzet_rupiah,catatan\n" .
            "A1,Rambak,2026,10,250,5000000,Import testing A1\n" .
            "A2,Blinjo,2026,10,180,4500000,Import testing A2\n";

        $file = UploadedFile::fake()->createWithContent('rekap_test.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('sales-recaps.import'), [
            'csv_file' => $file,
            'sync_dashboard' => 1,
            'target_year' => 2026,
            'target_month' => 10,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('monthly_sales_recaps', [
            'year' => 2026,
            'month' => 10,
            'total_units_sold' => 250,
        ]);
    }
}
