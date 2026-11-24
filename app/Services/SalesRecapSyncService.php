<?php

namespace App\Services;

use App\Models\CalculationRun;
use App\Models\Criterion;
use App\Models\MonthlySalesRecap;
use App\Models\Product;
use App\Models\ProductAssessment;
use App\Models\SalesTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesRecapSyncService
{
    public function __construct(
        protected SawService $sawService
    ) {}

    /**
     * Synchronize monthly sales recap aggregation into Criterion C1 (Tingkat Permintaan)
     * and auto-recalculate SAW for the active calculation run.
     *
     * @param CalculationRun $run
     * @return array
     */
    public function syncRecapToAssessments(CalculationRun $run): array
    {
        // 1. Find Criterion C1
        $c1 = Criterion::where('code', 'C1')->firstOrFail();

        // 2. Parse start_period and end_period (format: YYYY-MM)
        [$startYear, $startMonth] = explode('-', $run->start_period);
        [$endYear, $endMonth] = explode('-', $run->end_period);

        $startVal = ((int) $startYear) * 100 + ((int) $startMonth);
        $endVal = ((int) $endYear) * 100 + ((int) $endMonth);

        // 3. Aggregate total units sold per product from monthly_sales_recaps
        $recaps = MonthlySalesRecap::select('product_id', DB::raw('SUM(total_units_sold) as total_units'))
            ->whereRaw('(year * 100 + month) BETWEEN ? AND ?', [$startVal, $endVal])
            ->groupBy('product_id')
            ->pluck('total_units', 'product_id');

        $products = Product::where('is_active', true)->get();
        $syncedCounts = [];

        foreach ($products as $product) {
            $units = (int) ($recaps[$product->id] ?? 0);
            $syncedCounts[$product->id] = $units;

            // Update product_assessments for C1
            ProductAssessment::updateOrCreate(
                [
                    'calculation_run_id' => $run->id,
                    'product_id' => $product->id,
                    'criterion_id' => $c1->id,
                ],
                [
                    'raw_value' => $units,
                ]
            );
        }

        // 4. Auto-recalculate SAW results
        $sawResult = $this->sawService->calculateAndSave($run);

        // 5. Update timestamp of run
        $run->touch();

        return [
            'calculation_run_id' => $run->id,
            'period' => "{$run->start_period} s.d. {$run->end_period}",
            'synced_products_count' => count($syncedCounts),
            'total_units_aggregated' => array_sum($syncedCounts),
            'saw_results_count' => count($sawResult['rankings']),
            'top_product' => $sawResult['rankings'][0]['product_name'] ?? null,
            'top_score' => $sawResult['rankings'][0]['final_score'] ?? null,
        ];
    }

    /**
     * Populate or re-aggregate monthly_sales_recaps from detailed sales_transactions table.
     */
    public function aggregateTransactionsToRecaps(?int $userId = null): int
    {
        $aggregates = SalesTransaction::select(
            'product_id',
            'year',
            'month',
            DB::raw('SUM(qty_clean) as total_sold'),
            DB::raw('SUM(clean_turnover) as total_rev')
        )
            ->whereNotNull('product_id')
            ->groupBy('product_id', 'year', 'month')
            ->get();

        $count = 0;
        foreach ($aggregates as $agg) {
            MonthlySalesRecap::updateOrCreate(
                [
                    'product_id' => $agg->product_id,
                    'year' => $agg->year,
                    'month' => $agg->month,
                ],
                [
                    'total_units_sold' => (int) $agg->total_sold,
                    'total_revenue' => (float) $agg->total_rev,
                    'notes' => 'Agregasi otomatis dari data transaksi nota Camilan Jimerto',
                    'input_by' => $userId,
                ]
            );
            $count++;
        }

        return $count;
    }
}

