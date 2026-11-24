<?php

namespace App\Http\Controllers;

use App\Models\CalculationRun;
use App\Models\Criterion;
use App\Models\MonthlySalesRecap;
use App\Models\Product;
use App\Models\SawResult;
use App\Services\SawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected SawService $sawService
    ) {}

    public function index(Request $request)
    {
        // 1. Get active or selected calculation run
        $runs = CalculationRun::orderByDesc('is_active_dashboard')->orderByDesc('id')->get();
        $selectedRunId = $request->query('run_id');

        if ($selectedRunId) {
            $currentRun = CalculationRun::findOrFail($selectedRunId);
        } else {
            $currentRun = CalculationRun::where('is_active_dashboard', true)->first() ?? $runs->first();
        }

        if (!$currentRun) {
            return view('dashboard.empty', ['runs' => $runs]);
        }

        // 2. Fetch Criteria and Weights
        $criteria = Criterion::orderBy('id')->get();
        $weights = $currentRun->weights()->pluck('weight', 'criterion_id')->toArray();

        // 3. Fetch SAW Rankings for Current Run
        $sawResults = SawResult::with('product')
            ->where('calculation_run_id', $currentRun->id)
            ->orderBy('rank')
            ->get();

        // If results don't exist yet, compute on the fly
        if ($sawResults->isEmpty()) {
            $calcData = $this->sawService->calculateAndSave($currentRun);
            $sawResults = SawResult::with('product')
                ->where('calculation_run_id', $currentRun->id)
                ->orderBy('rank')
                ->get();
        }

        // 4. KPI Calculations
        $topProduct = $sawResults->first();
        
        // Total Volume Permintaan Periode Aktif
        [$startYear, $startMonth] = explode('-', $currentRun->start_period);
        [$endYear, $endMonth] = explode('-', $currentRun->end_period);
        $startVal = ((int) $startYear) * 100 + ((int) $startMonth);
        $endVal = ((int) $endYear) * 100 + ((int) $endMonth);

        $totalDemandUnits = (int) MonthlySalesRecap::whereRaw('(year * 100 + month) BETWEEN ? AND ?', [$startVal, $endVal])
            ->sum('total_units_sold');

        // Most influential criterion
        $mostInfluentialCrit = null;
        $maxWeight = -1;
        foreach ($criteria as $crit) {
            $w = $weights[$crit->id] ?? 0;
            if ($w > $maxWeight) {
                $maxWeight = $w;
                $mostInfluentialCrit = $crit;
            }
        }

        // Tier counts
        $tierCounts = [
            'utama' => $sawResults->where('priority_tier', 'utama')->count(),
            'menengah' => $sawResults->where('priority_tier', 'menengah')->count(),
            'rendah' => $sawResults->where('priority_tier', 'rendah')->count(),
        ];

        // 5. Build Chart Data
        // Horizontal Bar Chart: 30 Products
        $chartCategories = $sawResults->pluck('product.name')->toArray();
        $chartScores = $sawResults->pluck('final_score')->map(fn($v) => (float) $v)->toArray();
        $chartProductIds = $sawResults->pluck('product_id')->toArray();
        $chartTiers = $sawResults->pluck('priority_tier')->toArray();

        // AHP Weights Chart Data
        $ahpChartLabels = $criteria->pluck('code')->map(fn($c, $idx) => "$c - " . $criteria[$idx]->name)->toArray();
        $ahpChartValues = $criteria->map(fn($c) => round(($weights[$c->id] ?? 0) * 100, 2))->toArray();
        $ahpChartIds = $criteria->pluck('id')->toArray();

        // 6. Assessments Matrix for Drilldown & Comparison
        $assessments = $currentRun->assessments()->get()->groupBy('product_id');

        return view('dashboard.index', compact(
            'runs',
            'currentRun',
            'criteria',
            'weights',
            'sawResults',
            'topProduct',
            'totalDemandUnits',
            'mostInfluentialCrit',
            'maxWeight',
            'tierCounts',
            'chartCategories',
            'chartScores',
            'chartProductIds',
            'chartTiers',
            'ahpChartLabels',
            'ahpChartValues',
            'ahpChartIds',
            'assessments'
        ));
    }

    /**
     * AJAX Drill-down details for a specific product
     */
    public function drilldown(Request $request, int $productId)
    {
        $runId = $request->query('run_id');
        $run = $runId ? CalculationRun::findOrFail($runId) : CalculationRun::where('is_active_dashboard', true)->firstOrFail();
        $product = Product::findOrFail($productId);
        $criteria = Criterion::orderBy('id')->get();

        // Fetch SAW full calculation for breakdown
        $calc = $this->sawService->calculate($run);
        $rankItem = collect($calc['rankings'])->firstWhere('product_id', $productId);

        if (!$rankItem) {
            return response()->json(['error' => 'Data produk tidak ditemukan'], 404);
        }

        // Monthly sales recaps history for this product
        $monthlyRecaps = MonthlySalesRecap::where('product_id', $productId)
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(fn($r) => [
                'period' => sprintf('%04d-%02d', $r->year, $r->month),
                'year' => $r->year,
                'month' => $r->month,
                'month_name' => date('F', mktime(0, 0, 0, $r->month, 10)),
                'units' => $r->total_units_sold,
                'revenue' => (float) $r->total_revenue,
            ]);

        // Build criteria breakdown table
        $breakdownTable = [];
        $stackedChartSeries = [];
        $radarScores = [];

        foreach ($criteria as $crit) {
            $raw = $rankItem['raw_values'][$crit->id] ?? 0;
            $norm = $rankItem['normalized_values'][$crit->id] ?? 0;
            $weight = $calc['weights'][$crit->id] ?? 0;
            $weighted = $rankItem['weighted_values'][$crit->id] ?? 0;

            $breakdownTable[] = [
                'criterion_id' => $crit->id,
                'criterion_code' => $crit->code,
                'criterion_name' => $crit->name,
                'attribute_type' => $crit->attribute_type,
                'unit_or_scale' => $crit->unit_or_scale,
                'raw_value' => $raw,
                'max_or_min' => ($crit->attribute_type === 'benefit') ? $calc['max_values'][$crit->id] : $calc['min_values'][$crit->id],
                'normalized_value' => $norm,
                'weight' => round($weight, 4),
                'weighted_contribution' => $weighted,
                'percentage_contribution' => ($rankItem['final_score'] > 0) ? round(($weighted / $rankItem['final_score']) * 100, 1) : 0,
            ];

            $radarScores[] = round($norm * 100, 1);
        }

        return response()->json([
            'product' => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
            ],
            'ranking' => [
                'rank' => $rankItem['rank'],
                'final_score' => $rankItem['final_score'],
                'priority_tier' => $rankItem['priority_tier'],
                'tier_label' => $rankItem['tier_label'],
                'tier_badge' => $rankItem['tier_badge'],
                'narrative' => $rankItem['narrative'],
            ],
            'breakdown' => $breakdownTable,
            'radar_labels' => $criteria->pluck('code')->toArray(),
            'radar_values' => $radarScores,
            'monthly_sales' => $monthlyRecaps,
            'evaluation_period' => "{$run->start_period} s.d. {$run->end_period}",
        ]);
    }

    /**
     * AJAX Drill-down for a Criterion: distribution of 30 products on that criterion
     */
    public function criterionDrilldown(Request $request, int $criterionId)
    {
        $runId = $request->query('run_id');
        $run = $runId ? CalculationRun::findOrFail($runId) : CalculationRun::where('is_active_dashboard', true)->firstOrFail();
        $criterion = Criterion::findOrFail($criterionId);

        $calc = $this->sawService->calculate($run);
        $weight = $calc['weights'][$criterionId] ?? 0;

        $items = [];
        foreach ($calc['rankings'] as $r) {
            $raw = $r['raw_values'][$criterionId] ?? 0;
            $norm = $r['normalized_values'][$criterionId] ?? 0;
            $weighted = $r['weighted_values'][$criterionId] ?? 0;

            $items[] = [
                'product_id' => $r['product_id'],
                'product_code' => $r['product_code'],
                'product_name' => $r['product_name'],
                'overall_rank' => $r['rank'],
                'raw_value' => $raw,
                'normalized_value' => $norm,
                'weighted_value' => $weighted,
            ];
        }

        // Sort descending by raw_value (or ascending for cost)
        if ($criterion->attribute_type === 'cost') {
            usort($items, fn($a, $b) => $a['raw_value'] <=> $b['raw_value']);
        } else {
            usort($items, fn($a, $b) => $b['raw_value'] <=> $a['raw_value']);
        }

        return response()->json([
            'criterion' => $criterion,
            'weight' => round($weight, 4),
            'products' => $items,
        ]);
    }

    /**
     * AJAX What-If Sensitivity Simulator API
     */
    public function simulate(Request $request)
    {
        $validated = $request->validate([
            'run_id' => 'required|exists:calculation_runs,id',
            'weights' => 'required|array', // [criterion_id => weight_pct]
        ]);

        $run = CalculationRun::findOrFail($validated['run_id']);
        
        // Convert percentage inputs to decimal weights summing to 1.0
        $rawWeights = $validated['weights'];
        $sum = array_sum($rawWeights);
        $decimalWeights = [];
        foreach ($rawWeights as $cId => $val) {
            $decimalWeights[(int) $cId] = ($sum > 0) ? ($val / $sum) : 0.2;
        }

        // Get baseline rankings for rank difference calculation
        $baseCalc = $this->sawService->calculate($run);
        $baseRanks = collect($baseCalc['rankings'])->pluck('rank', 'product_id')->toArray();

        // Run simulation with override weights
        $simCalc = $this->sawService->calculate($run, $decimalWeights);
        $simRankings = [];

        foreach ($simCalc['rankings'] as $item) {
            $pId = $item['product_id'];
            $oldRank = $baseRanks[$pId] ?? $item['rank'];
            $rankDiff = $oldRank - $item['rank']; // positive means climbed up, negative means fell down

            $simRankings[] = [
                'product_id' => $pId,
                'product_code' => $item['product_code'],
                'product_name' => $item['product_name'],
                'final_score' => $item['final_score'],
                'rank' => $item['rank'],
                'old_rank' => $oldRank,
                'rank_diff' => $rankDiff,
                'priority_tier' => $item['priority_tier'],
                'tier_label' => $item['tier_label'],
                'tier_badge' => $item['tier_badge'],
            ];
        }

        return response()->json([
            'status' => 'success',
            'simulated_weights' => $decimalWeights,
            'rankings' => $simRankings,
        ]);
    }
}

