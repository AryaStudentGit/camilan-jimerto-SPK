<?php

namespace App\Http\Controllers;

use App\Models\CalculationRun;
use App\Models\Criterion;
use App\Services\AhpService;
use App\Services\SalesRecapSyncService;
use App\Services\SawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalculationRunController extends Controller
{
    public function __construct(
        protected AhpService $ahpService,
        protected SawService $sawService,
        protected SalesRecapSyncService $syncService
    ) {}

    public function index()
    {
        $runs = CalculationRun::with('user')
            ->withCount(['assessments', 'sawResults'])
            ->orderByDesc('is_active_dashboard')
            ->orderByDesc('id')
            ->get();

        return view('runs.index', compact('runs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_period' => 'required|date_format:Y-m',
            'end_period' => 'required|date_format:Y-m|after_or_equal:start_period',
        ]);

        $run = CalculationRun::create([
            'title' => $validated['title'],
            'start_period' => $validated['start_period'],
            'end_period' => $validated['end_period'],
            'is_consistent' => true,
            'is_active_dashboard' => false,
            'created_by' => Auth::id(),
        ]);

        // Copy AHP comparisons & operational assessments from latest run if available
        $latest = CalculationRun::where('id', '!=', $run->id)->latest('id')->first();
        if ($latest) {
            foreach ($latest->comparisons as $comp) {
                $run->comparisons()->create([
                    'criterion_1_id' => $comp->criterion_1_id,
                    'criterion_2_id' => $comp->criterion_2_id,
                    'comparison_value' => $comp->comparison_value,
                ]);
            }
            foreach ($latest->weights as $w) {
                $run->weights()->create([
                    'criterion_id' => $w->criterion_id,
                    'weight' => $w->weight,
                ]);
            }
            // Copy C2-C5 assessments
            foreach ($latest->assessments as $ass) {
                $crit = Criterion::find($ass->criterion_id);
                if ($crit && $crit->code !== 'C1') {
                    $run->assessments()->create([
                        'product_id' => $ass->product_id,
                        'criterion_id' => $ass->criterion_id,
                        'raw_value' => $ass->raw_value,
                    ]);
                }
            }
        }

        // Sync sales recap for C1 and compute SAW
        $this->syncService->syncRecapToAssessments($run);

        return redirect()->route('runs.index')->with('success', "Periode evaluasi '{$run->title}' berhasil dibuat.");
    }

    public function activate(int $id)
    {
        CalculationRun::where('is_active_dashboard', true)->update(['is_active_dashboard' => false]);
        $run = CalculationRun::findOrFail($id);
        $run->update(['is_active_dashboard' => true]);

        // Re-sync to ensure dashboard has the latest data
        $this->syncService->syncRecapToAssessments($run);

        return redirect()->route('dashboard', ['run_id' => $run->id])
            ->with('success', "Periode evaluasi '{$run->title}' telah diaktifkan sebagai Dashboard Utama.");
    }

    public function destroy(int $id)
    {
        $run = CalculationRun::findOrFail($id);
        if ($run->is_active_dashboard) {
            return back()->with('error', 'Tidak dapat menghapus periode yang sedang aktif di Dashboard.');
        }
        $run->delete();
        return back()->with('success', 'Periode evaluasi berhasil dihapus.');
    }
}

