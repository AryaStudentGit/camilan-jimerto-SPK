@extends('layouts.app')

@section('title', 'Rekap Penjualan Bulanan - Camilan Jimerto')

@section('content')
<div class="space-y-6" x-data="{ importModal: false }">

    <!-- Header & Period Filter -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Kelola Rekap Penjualan Bulanan</h1>
            <p class="text-xs text-slate-500 mt-1">
                Input dan perbarui data volume pesanan / unit terjual untuk seluruh 30 produk dalam satu tampilan matriks cepat.
            </p>
        </div>

        <!-- Period Selector -->
        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('sales-recaps.index') }}" class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500 uppercase">Tahun:</label>
                <select name="year" onchange="this.form.submit()" class="text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 outline-none">
                    <option value="2026" {{ $year == 2026 ? 'selected' : '' }}>2026</option>
                    <option value="2025" {{ $year == 2025 ? 'selected' : '' }}>2025</option>
                </select>

                <label class="text-xs font-bold text-slate-500 uppercase ml-2">Bulan:</label>
                <select name="month" onchange="this.form.submit()" class="text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 outline-none">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ sprintf('%02d - %s', $m, date('F', mktime(0, 0, 0, $m, 10))) }}
                        </option>
                    @endfor
                </select>
            </form>

            <a href="{{ route('sales-recaps.template', ['year' => $year, 'month' => $month]) }}" title="Unduh Template CSV Siap Isi" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 shadow-2xs transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Template CSV
            </a>

            <button type="button" @click="importModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-brand-50 border border-brand-200 hover:bg-brand-100 text-brand-800 transition">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import CSV
            </button>
        </div>
    </div>

    <!-- Monthly Summary Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Periode Aktif Input</div>
            <div class="text-lg font-black text-slate-900 mt-1">
                {{ sprintf('%04d-%02d (%s)', $year, $month, date('F Y', mktime(0, 0, 0, $month, 10, $year))) }}
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Unit Terjual Bulan Ini</div>
            <div class="text-lg font-black text-brand-600 mt-1">
                {{ number_format($totalUnitsMonth, 0, ',', '.') }} <span class="text-xs font-semibold text-slate-500">unit</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Estimasi Total Omzet</div>
            <div class="text-lg font-black text-emerald-600 mt-1">
                Rp {{ number_format($totalRevenueMonth, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Sales Matrix Form -->
    <form action="{{ route('sales-recaps.store') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Matriks Input Penjualan 30 Produk</h2>
                    <p class="text-xs text-slate-500">Edit unit terjual dan catatan langsung pada tabel di bawah ini.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" name="action" value="save_only" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Simpan Saja
                    </button>
                    <button type="submit" name="action" value="save_and_sync" class="px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-brand-600 to-amber-600 hover:from-brand-700 hover:to-amber-700 text-white shadow-md transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Simpan & Perbarui Dashboard
                    </button>
                </div>
            </div>

            <!-- Table Matrix -->
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-3 w-16">Kode</th>
                            <th class="p-3 w-64">Nama Produk Alternatif</th>
                            <th class="p-3 w-48 text-right">Jumlah Unit Terjual (C1)</th>
                            <th class="p-3 w-48 text-right">Total Pendapatan / Omzet (Rp)</th>
                            <th class="p-3">Catatan / Status Rekap</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($products as $idx => $prod)
                            @php
                                $recap = $recaps->get($prod->id);
                                $units = old("items.{$idx}.total_units_sold", $recap?->total_units_sold ?? 0);
                                $rev = old("items.{$idx}.total_revenue", $recap?->total_revenue ?? 0);
                                $notes = old("items.{$idx}.notes", $recap?->notes ?? '');
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3 font-bold text-slate-500">
                                    <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $prod->id }}">
                                    {{ $prod->code }}
                                </td>
                                <td class="p-3 font-bold text-slate-900">
                                    {{ $prod->name }}
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" step="1" name="items[{{ $idx }}][total_units_sold]" value="{{ $units }}" required
                                        class="w-36 text-right px-3 py-1.5 rounded-lg border border-slate-300 font-mono font-bold text-slate-900 focus:ring-2 focus:ring-brand-500 outline-none">
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" step="1000" name="items[{{ $idx }}][total_revenue]" value="{{ $rev }}"
                                        class="w-40 text-right px-3 py-1.5 rounded-lg border border-slate-300 font-mono text-slate-700 focus:ring-2 focus:ring-brand-500 outline-none">
                                </td>
                                <td class="p-3">
                                    <input type="text" name="items[{{ $idx }}][notes]" value="{{ $notes }}" placeholder="Misal: lonjakan pesanan grosir Waru"
                                        class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-600 focus:ring-2 focus:ring-brand-500 outline-none">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Bottom Action Bar -->
            <div class="flex items-center justify-between pt-3">
                <span class="text-xs text-slate-500">Menampilkan seluruh 30 produk aktif UMKM Camilan Jimerto.</span>
                <button type="submit" name="action" value="save_and_sync" class="px-5 py-2.5 rounded-xl text-xs font-extrabold bg-gradient-to-r from-brand-600 to-amber-600 hover:from-brand-700 hover:to-amber-700 text-white shadow-md transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Simpan & Perbarui Dashboard
                </button>
            </div>
        </div>
    </form>

    <!-- Modal Import CSV -->
    <div x-show="importModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="importModal = false"></div>
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 relative z-10 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Import Rekap Penjualan (CSV)</h3>
                        <p class="text-xs text-slate-500">Unggah berkas CSV untuk memperbarui data volume & omzet 30 produk.</p>
                    </div>
                </div>
                <button @click="importModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <!-- Download Template Box -->
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/70 rounded-xl p-4 flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Belum punya template CSV?
                    </div>
                    <div class="text-[11px] text-emerald-700 mt-0.5">Template sudah terisi 30 produk (A1 - A30) dengan format standar siap input.</div>
                </div>
                <div class="flex flex-col gap-1 shrink-0">
                    <a href="{{ route('sales-recaps.template', ['year' => $year, 'month' => $month]) }}"
                       class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Unduh Template (.csv)
                    </a>
                </div>
            </div>

            <form action="{{ route('sales-recaps.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="target_year" value="{{ $year }}">
                <input type="hidden" name="target_month" value="{{ $month }}">

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Pilih Berkas CSV:</label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required class="w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 border border-slate-200 rounded-xl">
                </div>

                <!-- Column mapping info -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-600 space-y-1.5">
                    <div class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">Format Kolom yang Didukung:</div>
                    <div class="font-mono text-[11px] text-brand-700 bg-white px-2 py-1.5 rounded border border-slate-200 overflow-x-auto">
                        kode_produk, nama_produk, tahun, bulan, total_unit_terjual, total_omzet_rupiah, catatan
                    </div>
                    <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-0.5">
                        <li>Mendukung pemisah koma (<code>,</code>) maupun titik koma (<code>;</code>) khas Excel Indonesia.</li>
                        <li>Pencocokan produk otomatis mendeteksi Kode (A1–A30) atau Nama Produk.</li>
                    </ul>
                </div>

                <!-- Auto sync checkbox -->
                <div class="bg-amber-50/60 border border-amber-200/60 rounded-xl p-3">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="sync_dashboard" value="1" checked class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">Otomatis Perbarui Dashboard SPK Aktif</span>
                            <span class="text-[11px] text-slate-500 block leading-tight mt-0.5">
                                Kriteria C1 (Volume Penjualan) & hasil perankingan SAW pada periode aktif akan otomatis dihitung ulang secara instan.
                            </span>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="importModal = false" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-600 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Unggah & Proses Import
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

