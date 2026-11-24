<?php

namespace App\Http\Controllers;

use App\Models\CalculationRun;
use App\Models\MonthlySalesRecap;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Services\SalesRecapSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesRecapController extends Controller
{
    public function __construct(
        protected SalesRecapSyncService $syncService
    ) {}

    /**
     * Display Monthly Sales Matrix input view for all 30 products.
     */
    public function index(Request $request)
    {
        $year = (int) $request->query('year', 2026);
        $month = (int) $request->query('month', 10);

        $products = Product::where('is_active', true)->orderBy('id')->get();

        // Get existing recaps for the selected year and month
        $recaps = MonthlySalesRecap::where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('product_id');

        // Available periods for dropdown
        $availablePeriods = MonthlySalesRecap::select('year', 'month')
            ->distinct()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn($r) => [
                'year' => $r->year,
                'month' => $r->month,
                'label' => sprintf('%04d-%02d (%s %04d)', $r->year, $r->month, date('F', mktime(0, 0, 0, $r->month, 10)), $r->year),
            ]);

        // Total sales this month
        $totalUnitsMonth = $recaps->sum('total_units_sold');
        $totalRevenueMonth = $recaps->sum('total_revenue');

        $activeRun = CalculationRun::where('is_active_dashboard', true)->first();

        return view('sales-recaps.index', compact(
            'products',
            'recaps',
            'year',
            'month',
            'availablePeriods',
            'totalUnitsMonth',
            'totalRevenueMonth',
            'activeRun'
        ));
    }

    /**
     * Batch store / update monthly sales matrix.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2035',
            'month' => 'required|integer|min:1|max:12',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.total_units_sold' => 'required|integer|min:0',
            'items.*.total_revenue' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
            'action' => 'required|string|in:save_only,save_and_sync',
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $userId = Auth::id();

        DB::transaction(function () use ($validated, $year, $month, $userId) {
            foreach ($validated['items'] as $item) {
                MonthlySalesRecap::updateOrCreate(
                    [
                        'product_id' => $item['product_id'],
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'total_units_sold' => (int) $item['total_units_sold'],
                        'total_revenue' => isset($item['total_revenue']) ? (float) $item['total_revenue'] : 0,
                        'notes' => $item['notes'] ?? null,
                        'input_by' => $userId,
                    ]
                );
            }
        });

        // If requested "Simpan & Perbarui Dashboard", sync to active run
        if ($validated['action'] === 'save_and_sync') {
            $activeRun = CalculationRun::where('is_active_dashboard', true)->first();
            if ($activeRun) {
                $syncResult = $this->syncService->syncRecapToAssessments($activeRun);
                return redirect()->route('dashboard')
                    ->with('success', "Rekap penjualan berhasil disimpan! Dashboard aktif '{$activeRun->title}' telah otomatis diperbarui (Total {$syncResult['total_units_aggregated']} unit tersinkron).");
            }
        }

        return redirect()->route('sales-recaps.index', ['year' => $year, 'month' => $month])
            ->with('success', 'Data rekap penjualan bulanan berhasil disimpan.');
    }

    /**
     * View detailed historical sales transactions from the Jimerto dataset.
     */
    public function transactions(Request $request)
    {
        $query = SalesTransaction::with('product');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_item_name', 'like', "%{$search}%")
                  ->orWhere('standard_product_name', 'like', "%{$search}%")
                  ->orWhere('original_store_name', 'like', "%{$search}%")
                  ->orWhere('standard_store_name', 'like', "%{$search}%")
                  ->orWhere('invoice_no', 'like', "%{$search}%")
                  ->orWhere('row_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('month')) {
            $query->where('month', (int) $request->month);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->product_id);
        }

        $transactions = $query->orderBy('id', 'asc')->paginate(25)->withQueryString();
        $products = Product::where('is_active', true)->orderBy('code')->get();

        $stats = [
            'total_rows' => SalesTransaction::count(),
            'total_qty' => SalesTransaction::sum('qty_clean'),
            'total_turnover' => SalesTransaction::sum('clean_turnover'),
        ];

        return view('sales-recaps.transactions', compact('transactions', 'products', 'stats'));
    }

    /**
     * Download pre-filled, ready-to-use CSV template for monthly sales recap.
     */
    public function downloadTemplate(Request $request)
    {
        $year = (int) $request->query('year', 2026);
        $month = (int) $request->query('month', 10);
        $fillExisting = $request->boolean('fill_existing', false);

        $products = Product::where('is_active', true)->orderBy('id')->get();
        $existingRecaps = $fillExisting
            ? MonthlySalesRecap::where('year', $year)->where('month', $month)->get()->keyBy('product_id')
            : collect();

        $filename = sprintf('template_rekap_penjualan_jimerto_%04d_%02d.csv', $year, $month);

        return response()->streamDownload(function () use ($products, $existingRecaps, $year, $month) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for smooth opening in Microsoft Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row: clear Indonesian field names with standard English aliases support
            fputcsv($output, [
                'kode_produk',
                'nama_produk',
                'tahun',
                'bulan',
                'total_unit_terjual',
                'total_omzet_rupiah',
                'catatan',
            ]);

            foreach ($products as $prod) {
                $recap = $existingRecaps->get($prod->id);
                $units = $recap ? $recap->total_units_sold : 0;
                $revenue = $recap ? (float) $recap->total_revenue : 0;
                $notes = $recap ? $recap->notes : '';

                fputcsv($output, [
                    $prod->code,
                    $prod->name,
                    $year,
                    $month,
                    $units,
                    $revenue,
                    $notes,
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Import sales recaps from CSV with smart delimiter & header detection.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
            'sync_dashboard' => 'nullable',
            'target_year' => 'nullable|integer',
            'target_month' => 'nullable|integer|between:1,12',
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getRealPath();

        // Detect delimiter: sniff first non-empty line for ',' or ';'
        $firstLine = '';
        $fTest = fopen($filePath, 'r');
        if ($fTest) {
            while (($line = fgets($fTest)) !== false) {
                if (trim($line) !== '') {
                    $firstLine = $line;
                    break;
                }
            }
            fclose($fTest);
        }

        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 0, $delimiter);

        if (!$header) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong atau tidak valid.');
        }

        // Clean UTF-8 BOM
        if (isset($header[0])) {
            $header[0] = preg_replace('/[\x{FEFF}]/u', '', $header[0]);
        }

        // Map column indexes from header
        $colMap = [
            'code' => null,
            'name' => null,
            'year' => null,
            'month' => null,
            'units' => null,
            'revenue' => null,
            'notes' => null,
        ];

        foreach ($header as $idx => $rawCol) {
            $col = strtolower(trim(str_replace([' ', '_', '-', '"', "'"], '', $rawCol)));
            if (in_array($col, ['kodeproduk', 'kode', 'productcode', 'code'])) {
                $colMap['code'] = $idx;
            } elseif (in_array($col, ['namaproduk', 'nama', 'productname', 'name', 'itemname'])) {
                $colMap['name'] = $idx;
            } elseif (in_array($col, ['tahun', 'year', 'yr'])) {
                $colMap['year'] = $idx;
            } elseif (in_array($col, ['bulan', 'month', 'mo'])) {
                $colMap['month'] = $idx;
            } elseif (in_array($col, ['totalunitterjual', 'totalunit', 'totalunits', 'units', 'unit', 'qty', 'jumlah', 'volumepenjualan'])) {
                $colMap['units'] = $idx;
            } elseif (in_array($col, ['totalomzetrupiah', 'totalomzet', 'totalrevenue', 'omzet', 'revenue', 'omset', 'totalomset'])) {
                $colMap['revenue'] = $idx;
            } elseif (in_array($col, ['catatan', 'notes', 'keterangan', 'note'])) {
                $colMap['notes'] = $idx;
            }
        }

        // Fallback default index positions if headers didn't match names
        $numCols = count($header);
        if ($colMap['code'] === null) {
            $colMap['code'] = 0;
            if ($numCols >= 7) {
                $colMap['name'] = 1;
                $colMap['year'] = 2;
                $colMap['month'] = 3;
                $colMap['units'] = 4;
                $colMap['revenue'] = 5;
                $colMap['notes'] = 6;
            } elseif ($numCols >= 5) {
                $colMap['year'] = 1;
                $colMap['month'] = 2;
                $colMap['units'] = 3;
                $colMap['revenue'] = 4;
            }
        }

        $productsByCode = Product::all()->keyBy(fn($p) => strtoupper(trim($p->code)));
        $productsByName = Product::all()->keyBy(fn($p) => strtolower(trim($p->name)));

        $imported = 0;
        $defaultYear = (int) $request->input('target_year', 2026);
        $defaultMonth = (int) $request->input('target_month', 10);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty($row) || count(array_filter($row, fn($v) => trim($v) !== '')) === 0) {
                continue;
            }

            $rawCode = isset($colMap['code'], $row[$colMap['code']]) ? trim($row[$colMap['code']]) : '';
            $rawName = isset($colMap['name'], $row[$colMap['name']]) ? trim($row[$colMap['name']]) : '';

            $product = null;
            if ($rawCode !== '') {
                $product = $productsByCode->get(strtoupper($rawCode));
            }
            if (!$product && $rawName !== '') {
                $product = $productsByName->get(strtolower($rawName));
            }

            if (!$product) {
                continue;
            }

            $year = isset($colMap['year'], $row[$colMap['year']]) && trim($row[$colMap['year']]) !== ''
                ? (int) preg_replace('/[^\d]/', '', $row[$colMap['year']])
                : $defaultYear;

            $month = isset($colMap['month'], $row[$colMap['month']]) && trim($row[$colMap['month']]) !== ''
                ? (int) preg_replace('/[^\d]/', '', $row[$colMap['month']])
                : $defaultMonth;

            $unitsRaw = isset($colMap['units'], $row[$colMap['units']]) ? trim($row[$colMap['units']]) : '0';
            $units = (int) preg_replace('/[^\d]/', '', $unitsRaw);

            $revenueRaw = isset($colMap['revenue'], $row[$colMap['revenue']]) ? trim($row[$colMap['revenue']]) : '0';
            $revenueClean = preg_replace('/[^\d.]/', '', str_replace(['Rp', 'rp', ' ', ','], ['', '', '', ''], $revenueRaw));
            $revenue = (float) $revenueClean;

            $notes = isset($colMap['notes'], $row[$colMap['notes']]) ? trim($row[$colMap['notes']]) : 'Import CSV';

            MonthlySalesRecap::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'year' => $year ?: $defaultYear,
                    'month' => $month ?: $defaultMonth,
                ],
                [
                    'total_units_sold' => $units,
                    'total_revenue' => $revenue,
                    'notes' => $notes ?: 'Import CSV',
                    'input_by' => Auth::id(),
                ]
            );
            $imported++;
        }
        fclose($handle);

        if ($imported === 0) {
            return back()->with('error', 'Tidak ada data produk yang berhasil diimpor. Pastikan kolom kode_produk (A1 - A30) atau nama_produk sesuai.');
        }

        // Auto sync if requested
        if ($request->filled('sync_dashboard')) {
            $activeRun = CalculationRun::where('is_active_dashboard', true)->first();
            if ($activeRun) {
                $syncResult = $this->syncService->syncRecapToAssessments($activeRun);
                return redirect()->route('dashboard')
                    ->with('success', "Berhasil mengimpor {$imported} produk! Dashboard aktif '{$activeRun->title}' telah otomatis disinkronkan & diperbarui (Total {$syncResult['total_units_aggregated']} unit tersinkron).");
            }
        }

        return redirect()->route('sales-recaps.index', ['year' => $defaultYear, 'month' => $defaultMonth])
            ->with('success', "Berhasil mengimpor {$imported} data rekap penjualan produk.");
    }
}

