<?php

namespace Database\Seeders;

use App\Models\AhpComparison;
use App\Models\AhpWeight;
use App\Models\CalculationRun;
use App\Models\Criterion;
use App\Models\MonthlySalesRecap;
use App\Models\Product;
use App\Models\ProductAssessment;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Services\AhpService;
use App\Services\SalesRecapSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@jimerto.test'],
            [
                'name' => 'Admin Produksi Jimerto',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@jimerto.test'],
            [
                'name' => 'Bapak Jimerto (Pemilik UMKM)',
                'password' => Hash::make('password'),
                'role' => 'pemilik_umkm',
            ]
        );

        // 2. Criteria C1 - C5
        $criteriaData = [
            [
                'code' => 'C1',
                'name' => 'Tingkat Permintaan',
                'attribute_type' => 'benefit',
                'unit_or_scale' => 'Jumlah pesanan (unit)',
                'description' => 'Akumulasi volume pesanan / unit terjual dari rekap penjualan bulanan',
            ],
            [
                'code' => 'C2',
                'name' => 'Ketersediaan Bahan Baku',
                'attribute_type' => 'benefit',
                'unit_or_scale' => 'Skala 1 - 5',
                'description' => 'Tingkat kemudahan dan kestabilan pasokan bahan baku lokal di Pamekasan/Madura (1=Sangat Sulit, 5=Sangat Mudah)',
            ],
            [
                'code' => 'C3',
                'name' => 'Kemudahan Produksi',
                'attribute_type' => 'benefit',
                'unit_or_scale' => 'Skala 1 - 5',
                'description' => 'Tingkat kemudahan proses pembuatan, resep, dan ketersediaan tenaga kerja (1=Sangat Sulit, 5=Sangat Mudah)',
            ],
            [
                'code' => 'C4',
                'name' => 'Waktu Produksi',
                'attribute_type' => 'cost',
                'unit_or_scale' => 'Menit/batch',
                'description' => 'Durasi waktu pengolahan rata-rata per batch produksi (semakin singkat menit semakin efisien/baik)',
            ],
            [
                'code' => 'C5',
                'name' => 'Keuntungan Produk',
                'attribute_type' => 'benefit',
                'unit_or_scale' => 'Rupiah/unit',
                'description' => 'Margin laba kotor / kontribusi keuntungan per bungkus/unit produk',
            ],
        ];

        $criteria = [];
        foreach ($criteriaData as $c) {
            $criteria[$c['code']] = Criterion::firstOrCreate(['code' => $c['code']], $c);
        }

        // 3. 30 Products A1 - A30
        $productsList = [
            ['code' => 'A1',  'name' => 'Rambak'],
            ['code' => 'A2',  'name' => 'Blinjo'],
            ['code' => 'A3',  'name' => 'Rungterung'],
            ['code' => 'A4',  'name' => 'Rengginang'],
            ['code' => 'A5',  'name' => 'Keripik Tette Teri'],
            ['code' => 'A6',  'name' => 'Tales'],
            ['code' => 'A7',  'name' => 'Keripik Singkong Rasa'],
            ['code' => 'A8',  'name' => 'Keripik Pisang'],
            ['code' => 'A9',  'name' => 'Kacang Lorjuk'],
            ['code' => 'A10', 'name' => 'Sambal Goreng Teri'],
            ['code' => 'A11', 'name' => 'Otok Pedas Manis'],
            ['code' => 'A12', 'name' => 'Usus Ayam'],
            ['code' => 'A13', 'name' => 'Tempe Bergizi'],
            ['code' => 'A14', 'name' => 'Sus Kering'],
            ['code' => 'A15', 'name' => 'Plintiran Coklat'],
            ['code' => 'A16', 'name' => 'Keripik Kentang'],
            ['code' => 'A17', 'name' => 'Amplang Tengiri'],
            ['code' => 'A18', 'name' => 'Sus Coklat'],
            ['code' => 'A19', 'name' => 'Markoni Balado'],
            ['code' => 'A20', 'name' => 'Basreng Balado'],
            ['code' => 'A21', 'name' => 'Wafer Roll'],
            ['code' => 'A22', 'name' => 'Kedelai'],
            ['code' => 'A23', 'name' => 'Bidaran'],
            ['code' => 'A24', 'name' => 'Marning Pedas Manis'],
            ['code' => 'A25', 'name' => 'Keripik Tette Udang'],
            ['code' => 'A26', 'name' => 'Tela Ungu'],
            ['code' => 'A27', 'name' => 'Gaddung'],
            ['code' => 'A28', 'name' => 'Keripik Pedas'],
            ['code' => 'A29', 'name' => 'Keripik Tahu'],
            ['code' => 'A30', 'name' => 'Keciput'],
        ];

        $products = [];
        foreach ($productsList as $p) {
            $products[$p['code']] = Product::firstOrCreate(['code' => $p['code']], [
                'name' => $p['name'],
                'is_active' => true,
            ]);
        }

        // 4. Import CSV Transaction Dataset if exists
        $csvPath = database_path('data/camilan_jimerto_sales.csv');
        $productCodeLookup = [];
        foreach ($products as $code => $prod) {
            $productCodeLookup[$code] = $prod->id;
        }

        if (file_exists($csvPath)) {
            $file = fopen($csvPath, 'r');
            $header = fgetcsv($file);
            // Clean BOM from header
            if ($header && isset($header[0])) {
                $header[0] = preg_replace('/[\x{FEFF}]/u', '', $header[0]);
            }

            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < 10) continue;
                $rowData = array_combine($header, $row);

                // Map to product code
                $rawName = strtolower($rowData['Nama_Barang_Asli'] ?? '');
                $bakuName = strtolower($rowData['Nama_Produk_Baku'] ?? '');
                $assignedCode = $this->matchProductCode($rawName, $bakuName);
                $productId = $assignedCode ? ($productCodeLookup[$assignedCode] ?? null) : null;

                $year = (int) ($rowData['Tahun'] ?? 2026);
                $month = (int) ($rowData['Bulan_Angka'] ?? 8);
                $qtyClean = (int) ($rowData['Qty_Bersih'] ?? 0);
                $unitPrice = (float) ($rowData['Harga_Satuan'] ?? 0);
                $turnover = (float) ($rowData['Omzet_Bersih_Dashboard'] ?? ($qtyClean * $unitPrice));

                SalesTransaction::create([
                    'row_id' => $rowData['\ufeffID_Baris'] ?? ($rowData['ID_Baris'] ?? null),
                    'invoice_no' => $rowData['ID_Nota'] ?? null,
                    'transaction_date' => $rowData['Tanggal'] ?? null,
                    'year' => $year,
                    'month' => $month,
                    'month_period' => $rowData['Periode_Bulan'] ?? null,
                    'day_name' => $rowData['Hari'] ?? null,
                    'original_note_no' => $rowData['No_Nota_Asli'] ?? null,
                    'original_store_name' => $rowData['Nama_Toko_Asli'] ?? null,
                    'standard_store_name' => $rowData['Nama_Toko_Baku'] ?? null,
                    'district' => $rowData['Wilayah_Kecamatan'] ?? null,
                    'transaction_status' => $rowData['Status_Transaksi'] ?? null,
                    'original_item_name' => $rowData['Nama_Barang_Asli'] ?? '',
                    'sku_code' => $rowData['Kode_SKU'] ?? null,
                    'standard_product_name' => $rowData['Nama_Produk_Baku'] ?? null,
                    'product_id' => $productId,
                    'product_category' => $rowData['Kategori_Produk'] ?? null,
                    'qty_original' => (int) ($rowData['Qty_Asli'] ?? 0),
                    'qty_clean' => $qtyClean,
                    'unit_price' => $unitPrice,
                    'total_written' => (float) ($rowData['Jumlah_Tertulis_Nota'] ?? 0),
                    'total_valid' => (float) ($rowData['Jumlah_Valid_Hitung'] ?? 0),
                    'clean_turnover' => $turnover,
                    'difference_amount' => (float) ($rowData['Selisih_Tulis_Nota'] ?? 0),
                    'spelling_status' => $rowData['Status_Ejaan'] ?? null,
                    'spelling_notes' => $rowData['Keterangan_Ejaan_Typo'] ?? null,
                    'audit_notes' => $rowData['Catatan_Audit_Baris'] ?? null,
                    'photo_source' => $rowData['Sumber_Foto'] ?? null,
                ]);
            }
            fclose($file);
        }

        // 5. Seed Monthly Sales Recaps (August, September, October, November 2026)
        // Aggregate from SalesTransaction first
        $syncService = app(SalesRecapSyncService::class);
        $syncService->aggregateTransactionsToRecaps($admin->id);

        // Ensure every single one of the 30 products has realistic sales records for 2026-09 and 2026-10
        // (and 2026-08 & 2026-11) so no product is left blank
        $sampleSalesSept = [
            'A1' => 85,  'A2' => 105, 'A3' => 75,  'A4' => 630, 'A5' => 90,
            'A6' => 75,  'A7' => 65,  'A8' => 90,  'A9' => 45,  'A10' => 50,
            'A11' => 69, 'A12' => 80, 'A13' => 80, 'A14' => 25, 'A15' => 20,
            'A16' => 179,'A17' => 89, 'A18' => 40, 'A19' => 87, 'A20' => 40,
            'A21' => 15, 'A22' => 20, 'A23' => 49, 'A24' => 40, 'A25' => 35,
            'A26' => 60, 'A27' => 50, 'A28' => 30, 'A29' => 25, 'A30' => 30,
        ];

        $sampleSalesOct = [
            'A1' => 70,  'A2' => 85,  'A3' => 60,  'A4' => 550, 'A5' => 80,
            'A6' => 65,  'A7' => 55,  'A8' => 80,  'A9' => 40,  'A10' => 45,
            'A11' => 60, 'A12' => 75, 'A13' => 70, 'A14' => 25, 'A15' => 20,
            'A16' => 160,'A17' => 75, 'A18' => 35, 'A19' => 75, 'A20' => 35,
            'A21' => 15, 'A22' => 20, 'A23' => 40, 'A24' => 35, 'A25' => 30,
            'A26' => 55, 'A27' => 45, 'A28' => 25, 'A29' => 20, 'A30' => 30,
        ];

        $sampleSalesNov = [
            'A1' => 80,  'A2' => 75,  'A3' => 50,  'A4' => 490, 'A5' => 70,
            'A6' => 83,  'A7' => 50,  'A8' => 115, 'A9' => 35,  'A10' => 40,
            'A11' => 90, 'A12' => 58, 'A13' => 65, 'A14' => 20, 'A15' => 15,
            'A16' => 70, 'A17' => 74, 'A18' => 20, 'A19' => 65, 'A20' => 30,
            'A21' => 10, 'A22' => 20, 'A23' => 35, 'A24' => 40, 'A25' => 25,
            'A26' => 60, 'A27' => 30, 'A28' => 20, 'A29' => 19, 'A30' => 20,
        ];

        foreach ($products as $code => $prod) {
            // Update or create September 2026 recap
            $curSep = MonthlySalesRecap::where('product_id', $prod->id)->where('year', 2026)->where('month', 9)->first();
            $sepUnits = $curSep && $curSep->total_units_sold > 0 ? $curSep->total_units_sold : $sampleSalesSept[$code];
            MonthlySalesRecap::updateOrCreate(
                ['product_id' => $prod->id, 'year' => 2026, 'month' => 9],
                [
                    'total_units_sold' => $sepUnits,
                    'total_revenue' => $curSep && $curSep->total_revenue > 0 ? $curSep->total_revenue : ($sepUnits * 13500),
                    'notes' => 'Rekap penjualan September 2026',
                    'input_by' => $admin->id,
                ]
            );

            // Update or create October 2026 recap
            $curOct = MonthlySalesRecap::where('product_id', $prod->id)->where('year', 2026)->where('month', 10)->first();
            $octUnits = $curOct && $curOct->total_units_sold > 0 ? $curOct->total_units_sold : $sampleSalesOct[$code];
            MonthlySalesRecap::updateOrCreate(
                ['product_id' => $prod->id, 'year' => 2026, 'month' => 10],
                [
                    'total_units_sold' => $octUnits,
                    'total_revenue' => $curOct && $curOct->total_revenue > 0 ? $curOct->total_revenue : ($octUnits * 13500),
                    'notes' => 'Rekap penjualan Oktober 2026',
                    'input_by' => $admin->id,
                ]
            );

            // Update or create November 2026 recap
            $curNov = MonthlySalesRecap::where('product_id', $prod->id)->where('year', 2026)->where('month', 11)->first();
            $novUnits = $curNov && $curNov->total_units_sold > 0 ? $curNov->total_units_sold : $sampleSalesNov[$code];
            MonthlySalesRecap::updateOrCreate(
                ['product_id' => $prod->id, 'year' => 2026, 'month' => 11],
                [
                    'total_units_sold' => $novUnits,
                    'total_revenue' => $curNov && $curNov->total_revenue > 0 ? $curNov->total_revenue : ($novUnits * 13500),
                    'notes' => 'Rekap penjualan November 2026',
                    'input_by' => $admin->id,
                ]
            );
        }

        // 6. Active Calculation Run (Evaluasi Periode September - Oktober 2026)
        $run = CalculationRun::create([
            'title' => 'Evaluasi Prioritas Produksi Periode Sep - Okt 2026',
            'start_period' => '2026-09',
            'end_period' => '2026-10',
            'lambda_max' => 5.000000,
            'consistency_index' => 0.000000,
            'consistency_ratio' => 0.000000,
            'is_consistent' => true,
            'is_active_dashboard' => true,
            'created_by' => $admin->id,
        ]);

        // Secondary historical run for comparison (Evaluasi Periode Okt - Nov 2026)
        $runNov = CalculationRun::create([
            'title' => 'Evaluasi Prioritas Produksi Periode Okt - Nov 2026',
            'start_period' => '2026-10',
            'end_period' => '2026-11',
            'lambda_max' => 5.000000,
            'consistency_index' => 0.000000,
            'consistency_ratio' => 0.000000,
            'is_consistent' => true,
            'is_active_dashboard' => false,
            'created_by' => $owner->id,
        ]);

        // 7. Seed AHP Comparisons & Weights
        // Default target weights: C1=0.30, C2=0.20, C3=0.20, C4=0.15, C5=0.15
        $pairwiseMap = [
            "{$criteria['C1']->id}_{$criteria['C2']->id}" => 1.5,
            "{$criteria['C1']->id}_{$criteria['C3']->id}" => 1.5,
            "{$criteria['C1']->id}_{$criteria['C4']->id}" => 2.0,
            "{$criteria['C1']->id}_{$criteria['C5']->id}" => 2.0,
            "{$criteria['C2']->id}_{$criteria['C3']->id}" => 1.0,
            "{$criteria['C2']->id}_{$criteria['C4']->id}" => 1.3333,
            "{$criteria['C2']->id}_{$criteria['C5']->id}" => 1.3333,
            "{$criteria['C3']->id}_{$criteria['C4']->id}" => 1.3333,
            "{$criteria['C3']->id}_{$criteria['C5']->id}" => 1.3333,
            "{$criteria['C4']->id}_{$criteria['C5']->id}" => 1.0,
        ];

        $ahpService = app(AhpService::class);
        $criteriaColl = Criterion::orderBy('id')->get();
        $ahpService->saveCalculation($run, $pairwiseMap, $criteriaColl);
        $ahpService->saveCalculation($runNov, $pairwiseMap, $criteriaColl);

        // 8. Seed Operational Assessments C2, C3, C4, C5 for 30 products
        // Operational profiles reflect Madura snack manufacturing characteristics
        $operationalData = [
            'A1'  => ['C2' => 4, 'C3' => 4, 'C4' => 45, 'C5' => 3500],  // Rambak
            'A2'  => ['C2' => 4, 'C3' => 3, 'C4' => 60, 'C5' => 4000],  // Blinjo
            'A3'  => ['C2' => 3, 'C3' => 3, 'C4' => 75, 'C5' => 6000],  // Rungterung
            'A4'  => ['C2' => 5, 'C3' => 5, 'C4' => 35, 'C5' => 4500],  // Rengginang
            'A5'  => ['C2' => 4, 'C3' => 4, 'C4' => 50, 'C5' => 3000],  // Keripik Tette Teri
            'A6'  => ['C2' => 4, 'C3' => 4, 'C4' => 45, 'C5' => 3000],  // Tales
            'A7'  => ['C2' => 5, 'C3' => 4, 'C4' => 40, 'C5' => 4000],  // Keripik Singkong Rasa
            'A8'  => ['C2' => 5, 'C3' => 4, 'C4' => 45, 'C5' => 3500],  // Keripik Pisang
            'A9'  => ['C2' => 2, 'C3' => 3, 'C4' => 90, 'C5' => 7000],  // Kacang Lorjuk
            'A10' => ['C2' => 3, 'C3' => 3, 'C4' => 80, 'C5' => 5000],  // Sambal Goreng Teri
            'A11' => ['C2' => 4, 'C3' => 4, 'C4' => 50, 'C5' => 4000],  // Otok Pedas Manis
            'A12' => ['C2' => 4, 'C3' => 3, 'C4' => 70, 'C5' => 4500],  // Usus Ayam
            'A13' => ['C2' => 5, 'C3' => 4, 'C4' => 45, 'C5' => 3000],  // Tempe Bergizi
            'A14' => ['C2' => 3, 'C3' => 3, 'C4' => 65, 'C5' => 4500],  // Sus Kering
            'A15' => ['C2' => 3, 'C3' => 3, 'C4' => 60, 'C5' => 4000],  // Plintiran Coklat
            'A16' => ['C2' => 4, 'C3' => 4, 'C4' => 40, 'C5' => 3500],  // Keripik Kentang
            'A17' => ['C2' => 4, 'C3' => 4, 'C4' => 50, 'C5' => 4000],  // Amplang Tengiri
            'A18' => ['C2' => 3, 'C3' => 3, 'C4' => 65, 'C5' => 5000],  // Sus Coklat
            'A19' => ['C2' => 5, 'C3' => 5, 'C4' => 30, 'C5' => 2500],  // Markoni Balado
            'A19' => ['C2' => 5, 'C3' => 5, 'C4' => 30, 'C5' => 2500],  // Markoni Balado
            'A20' => ['C2' => 4, 'C3' => 4, 'C4' => 40, 'C5' => 3000],  // Basreng Balado
            'A21' => ['C2' => 3, 'C3' => 2, 'C4' => 75, 'C5' => 3500],  // Wafer Roll
            'A22' => ['C2' => 4, 'C3' => 4, 'C4' => 40, 'C5' => 3500],  // Kedelai
            'A23' => ['C2' => 4, 'C3' => 4, 'C4' => 50, 'C5' => 4000],  // Bidaran
            'A24' => ['C2' => 4, 'C3' => 4, 'C4' => 45, 'C5' => 4000],  // Marning Pedas Manis
            'A25' => ['C2' => 3, 'C3' => 3, 'C4' => 60, 'C5' => 4500],  // Keripik Tette Udang
            'A26' => ['C2' => 4, 'C3' => 4, 'C4' => 45, 'C5' => 3000],  // Tela Ungu
            'A27' => ['C2' => 3, 'C3' => 2, 'C4' => 85, 'C5' => 4500],  // Gaddung
            'A28' => ['C2' => 4, 'C3' => 4, 'C4' => 45, 'C5' => 3000],  // Keripik Pedas
            'A29' => ['C2' => 4, 'C3' => 3, 'C4' => 55, 'C5' => 3000],  // Keripik Tahu
            'A30' => ['C2' => 4, 'C3' => 4, 'C4' => 50, 'C5' => 4000],  // Keciput
        ];

        foreach ([$run, $runNov] as $activeRun) {
            foreach ($products as $code => $prod) {
                foreach (['C2', 'C3', 'C4', 'C5'] as $cCode) {
                    ProductAssessment::updateOrCreate(
                        [
                            'calculation_run_id' => $activeRun->id,
                            'product_id' => $prod->id,
                            'criterion_id' => $criteria[$cCode]->id,
                        ],
                        [
                            'raw_value' => $operationalData[$code][$cCode] ?? 3,
                        ]
                    );
                }
            }

            // 9. Synchronize C1 from monthly_sales_recaps and recalculate SAW
            $syncService->syncRecapToAssessments($activeRun);
        }
    }

    /**
     * Helper to match raw item name to product code A1-A30
     */
    private function matchProductCode(string $raw, string $baku): ?string
    {
        $raw = strtolower($raw);
        $baku = strtolower($baku);

        if (str_contains($raw, 'rung') || str_contains($baku, 'rung')) return 'A3';
        if (str_contains($raw, 'tette teri') || str_contains($baku, 'tette teri')) return 'A5';
        if (str_contains($raw, 'tette udang') || str_contains($baku, 'tette udang')) return 'A25';
        if (str_contains($raw, 'tette') || str_contains($baku, 'tette')) return 'A5';
        if (str_contains($raw, 'rengginang') || str_contains($baku, 'rengginang') || str_contains($raw, 'reng')) return 'A4';
        if (str_contains($raw, 'rambak') || str_contains($baku, 'rambak')) return 'A1';
        if (str_contains($raw, 'blinjo') || str_contains($baku, 'melinjo')) return 'A2';
        if (str_contains($raw, 'tales') || str_contains($baku, 'talas')) return 'A6';
        if (str_contains($raw, 'singkong') || str_contains($baku, 'singkong')) {
            return (str_contains($raw, 'pedas') && str_contains($raw, 'keripik')) ? 'A28' : 'A7';
        }
        if (str_contains($raw, 'pisang') || str_contains($baku, 'pisang')) return 'A8';
        if (str_contains($raw, 'lorjuk') || str_contains($baku, 'lorjuk')) return 'A9';
        if (str_contains($raw, 'sambal') || str_contains($baku, 'teri')) return 'A10';
        if (str_contains($raw, 'otok') || str_contains($baku, 'otok')) return 'A11';
        if (str_contains($raw, 'usus') || str_contains($baku, 'usus')) return 'A12';
        if (str_contains($raw, 'tempe') || str_contains($baku, 'tempe')) return 'A13';
        if (str_contains($raw, 'sus coklat') || str_contains($baku, 'sus coklat') || str_contains($raw, 'sus. coklat')) return 'A18';
        if (str_contains($raw, 'sus kering') || str_contains($baku, 'sus kering') || (str_contains($raw, 'sus') && !str_contains($raw, 'coklat'))) return 'A14';
        if (str_contains($raw, 'plintiran') || str_contains($baku, 'pelintiran') || str_contains($raw, 'puntiran')) return 'A15';
        if (str_contains($raw, 'kentang') || str_contains($baku, 'kentang')) return 'A16';
        if (str_contains($raw, 'amplang') || str_contains($baku, 'amplang')) return 'A17';
        if (str_contains($raw, 'markoni') || str_contains($baku, 'makaroni')) return 'A19';
        if (str_contains($raw, 'basreng') || str_contains($baku, 'basreng')) return 'A20';
        if (str_contains($raw, 'wafer') || str_contains($baku, 'wafer')) return 'A21';
        if (str_contains($raw, 'kedelai') || str_contains($baku, 'kedelai')) return 'A22';
        if (str_contains($raw, 'bidaran') || str_contains($baku, 'bidaran')) return 'A23';
        if (str_contains($raw, 'marning') || str_contains($baku, 'marning')) return 'A24';
        if (str_contains($raw, 'tela') || str_contains($baku, 'ketela') || str_contains($baku, 'ubi')) return 'A26';
        if (str_contains($raw, 'gaddung') || str_contains($baku, 'gadung')) return 'A27';
        if (str_contains($raw, 'pedas') && str_contains($raw, 'keripik')) return 'A28';
        if (str_contains($raw, 'tahu') || str_contains($baku, 'tahu')) return 'A29';
        if (str_contains($raw, 'keciput') || str_contains($baku, 'keciput')) return 'A30';

        return null;
    }
}
