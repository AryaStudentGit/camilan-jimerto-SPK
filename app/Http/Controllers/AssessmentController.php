<?php

namespace App\Http\Controllers;

use App\Models\CalculationRun;
use App\Models\Criterion;
use App\Models\Product;
use App\Models\ProductAssessment;
use App\Services\SawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    public function __construct(
        protected SawService $sawService
    ) {}

    /**
     * Matrix view for operational product assessments (C2, C3, C4, C5)
     */
    public function index(Request $request)
    {
        $runId = $request->query('run_id');
        $run = $runId ? CalculationRun::findOrFail($runId) : CalculationRun::where('is_active_dashboard', true)->firstOrFail();
        $runs = CalculationRun::orderByDesc('id')->get();

        $criteria = Criterion::orderBy('id')->get();
        $products = Product::where('is_active', true)->orderBy('id')->get();

        // Get assessments for current run
        $assessments = ProductAssessment::where('calculation_run_id', $run->id)
            ->get()
            ->groupBy('product_id');

        return view('assessments.index', compact('run', 'runs', 'criteria', 'products', 'assessments'));
    }

    /**
     * Batch update operational assessments and recalculate SAW.
     */
    public function update(Request $request, int $runId)
    {
        $run = CalculationRun::findOrFail($runId);
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.values' => 'required|array', // [criterion_id => raw_value]
        ]);

        DB::transaction(function () use ($validated, $run) {
            foreach ($validated['items'] as $item) {
                $pId = $item['product_id'];
                foreach ($item['values'] as $cId => $val) {
                    ProductAssessment::updateOrCreate(
                        [
                            'calculation_run_id' => $run->id,
                            'product_id' => $pId,
                            'criterion_id' => $cId,
                        ],
                        [
                            'raw_value' => (float) $val,
                        ]
                    );
                }
            }
        });

        // Recalculate SAW
        $this->sawService->calculateAndSave($run);

        return redirect()->route('assessments.index', ['run_id' => $run->id])
            ->with('success', 'Penilaian operasional 30 produk berhasil diperbarui dan peringkat SAW otomatis dihitung ulang.');
    }
}

