<?php

namespace App\Http\Controllers;

use App\Models\AhpComparison;
use App\Models\CalculationRun;
use App\Models\Criterion;
use App\Services\AhpService;
use App\Services\SawService;
use Illuminate\Http\Request;

class AhpController extends Controller
{
    public function __construct(
        protected AhpService $ahpService,
        protected SawService $sawService
    ) {}

    /**
     * Display AHP Pairwise Comparison Questionnaire (10 unique pairs for Pemilik UMKM).
     */
    public function index(Request $request)
    {
        $runId = $request->query('run_id');
        $run = $runId ? CalculationRun::findOrFail($runId) : CalculationRun::where('is_active_dashboard', true)->firstOrFail();
        $runs = CalculationRun::orderByDesc('id')->get();

        $criteria = Criterion::orderBy('id')->get();
        $n = $criteria->count();

        // Existing comparisons
        $existingComparisons = AhpComparison::where('calculation_run_id', $run->id)
            ->get()
            ->keyBy(fn($item) => "{$item->criterion_1_id}_{$item->criterion_2_id}");

        // Build 10 unique pairs
        $pairs = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $c1 = $criteria[$i];
                $c2 = $criteria[$j];
                $key = "{$c1->id}_{$c2->id}";
                $comp = $existingComparisons->get($key);
                $val = $comp ? (float) $comp->comparison_value : 1.0;

                // Determine scale direction for UI (1-9 scale toward left or right)
                $direction = ($val >= 1.0) ? 'left' : 'right';
                $scaleValue = ($val >= 1.0) ? round($val, 2) : round(1.0 / $val, 2);

                $pairs[] = [
                    'key' => $key,
                    'criterion_1' => $c1,
                    'criterion_2' => $c2,
                    'current_value' => $val,
                    'direction' => $direction,
                    'scale_value' => $scaleValue,
                ];
            }
        }

        // Current weights & consistency metrics
        $weights = $run->weights()->with('criterion')->get();

        return view('ahp.index', compact('run', 'runs', 'criteria', 'pairs', 'weights'));
    }

    /**
     * Update AHP Pairwise Comparisons, compute weights & consistency, auto-recalculate SAW.
     */
    public function update(Request $request, int $runId)
    {
        $run = CalculationRun::findOrFail($runId);
        $criteria = Criterion::orderBy('id')->get();

        $validated = $request->validate([
            'pairs' => 'required|array',
            'pairs.*.c1_id' => 'required|exists:criteria,id',
            'pairs.*.c2_id' => 'required|exists:criteria,id',
            'pairs.*.direction' => 'required|in:left,right,equal',
            'pairs.*.scale' => 'required|numeric|min:1|max:9',
        ]);

        $pairwiseMap = [];
        foreach ($validated['pairs'] as $pair) {
            $id1 = $pair['c1_id'];
            $id2 = $pair['c2_id'];
            $scale = (float) $pair['scale'];
            $dir = $pair['direction'];

            if ($dir === 'equal' || $scale == 1) {
                $val = 1.0;
            } elseif ($dir === 'left') {
                $val = $scale;
            } else { // right
                $val = 1.0 / $scale;
            }

            $pairwiseMap["{$id1}_{$id2}"] = $val;
        }

        // Calculate and save AHP
        $ahpResult = $this->ahpService->saveCalculation($run, $pairwiseMap, $criteria);

        // Auto-recalculate SAW results for this run
        $this->sawService->calculateAndSave($run);

        $cr = $ahpResult['consistency_ratio'];
        if ($ahpResult['is_consistent']) {
            $msg = "Kuesioner perbandingan AHP berhasil disimpan! Status Konsisten (CR = " . number_format($cr, 4) . " <= 0.10). Bobot prioritas kriteria dan peringkat SAW telah diperbarui.";
            return redirect()->route('ahp.index', ['run_id' => $run->id])->with('success', $msg);
        } else {
            $msg = "Perhatian: Rasio Konsistensi AHP melebihi batas (CR = " . number_format($cr, 4) . " > 0.10). Penilaian Anda inkonsisten, disarankan merevisi kuesioner agar hasil keputusan valid.";
            return redirect()->route('ahp.index', ['run_id' => $run->id])->with('warning', $msg);
        }
    }
}

