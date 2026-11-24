@extends('layouts.app')

@section('title', 'Penilaian Operasional Produk (C2 - C5) - Camilan Jimerto')

@section('content')
<div class="space-y-6">

    <!-- Header & Period Filter -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Penilaian Operasional Produk</h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola penilaian C2 (Bahan Baku), C3 (Kemudahan Produksi), C4 (Waktu Produksi), dan C5 (Keuntungan) untuk 30 produk.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('assessments.index') }}" class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500 uppercase">Periode:</label>
                <select name="run_id" onchange="this.form.submit()" class="text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 outline-none">
                    @foreach($runs as $r)
                        <option value="{{ $r->id }}" {{ $r->id == $run->id ? 'selected' : '' }}>
                            {{ $r->title }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- Important Notice Regarding C1 -->
    <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-900 flex items-start gap-3">
        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <span class="font-bold">Otomatisasi Kriteria C1 (Tingkat Permintaan):</span>
            Nilai untuk kriteria C1 tidak perlu diinput manual di halaman ini karena teragregasi secara otomatis dari tabel <strong>Rekap Penjualan Bulanan</strong> pada rentang periode evaluasi ({{ $run->start_period }} s.d. {{ $run->end_period }}).
        </div>
    </div>

    <!-- Operational Assessment Form -->
    <form action="{{ route('assessments.update', $run->id) }}" method="POST" class="space-y-4">
        @csrf

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Matriks Penilaian Operasional 30 Produk</h2>
                    <p class="text-xs text-slate-500">Edit skala bahan baku, kemudahan produksi, menit waktu pengerjaan, dan margin laba per bungkus.</p>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-extrabold bg-gradient-to-r from-brand-600 to-amber-600 hover:from-brand-700 hover:to-amber-700 text-white shadow-md transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan & Hitung Ulang SAW
                </button>
            </div>

            <!-- Table Matrix -->
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-3 w-16">Kode</th>
                            <th class="p-3 w-60">Nama Produk Alternatif</th>
                            <th class="p-3 text-center bg-blue-50/50 w-36">
                                C1 (Permintaan)
                                <span class="block text-[10px] text-slate-400 font-normal">Otomatis Rekap</span>
                            </th>
                            <th class="p-3 text-center w-40">
                                C2: Bahan Baku
                                <span class="block text-[10px] text-slate-400 font-normal">Benefit (Skala 1-5)</span>
                            </th>
                            <th class="p-3 text-center w-40">
                                C3: Kemudahan
                                <span class="block text-[10px] text-slate-400 font-normal">Benefit (Skala 1-5)</span>
                            </th>
                            <th class="p-3 text-center w-40">
                                C4: Waktu Produksi
                                <span class="block text-[10px] text-rose-500 font-normal">Cost (Menit/batch)</span>
                            </th>
                            <th class="p-3 text-center w-44">
                                C5: Keuntungan
                                <span class="block text-[10px] text-slate-400 font-normal">Benefit (Rp/unit)</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($products as $idx => $prod)
                            @php
                                $pAssess = $assessments->get($prod->id);
                                $rawC1 = $pAssess?->firstWhere('criterion_id', $criteria[0]->id)?->raw_value ?? 0;
                                $rawC2 = $pAssess?->firstWhere('criterion_id', $criteria[1]->id)?->raw_value ?? 3;
                                $rawC3 = $pAssess?->firstWhere('criterion_id', $criteria[2]->id)?->raw_value ?? 3;
                                $rawC4 = $pAssess?->firstWhere('criterion_id', $criteria[3]->id)?->raw_value ?? 45;
                                $rawC5 = $pAssess?->firstWhere('criterion_id', $criteria[4]->id)?->raw_value ?? 3500;
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3 font-bold text-slate-500">
                                    <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $prod->id }}">
                                    {{ $prod->code }}
                                </td>
                                <td class="p-3 font-bold text-slate-900">
                                    {{ $prod->name }}
                                </td>
                                <td class="p-3 text-center font-mono font-bold text-blue-700 bg-blue-50/20">
                                    {{ number_format($rawC1, 0, ',', '.') }} unit
                                </td>
                                <td class="p-3 text-center">
                                    <select name="items[{{ $idx }}][values][{{ $criteria[1]->id }}]" class="w-28 px-2 py-1.5 rounded-lg border border-slate-300 font-mono text-xs focus:ring-2 focus:ring-brand-500 outline-none">
                                        <option value="1" {{ $rawC2 == 1 ? 'selected' : '' }}>1 - Sangat Sulit</option>
                                        <option value="2" {{ $rawC2 == 2 ? 'selected' : '' }}>2 - Sulit</option>
                                        <option value="3" {{ $rawC2 == 3 ? 'selected' : '' }}>3 - Sedang</option>
                                        <option value="4" {{ $rawC2 == 4 ? 'selected' : '' }}>4 - Mudah</option>
                                        <option value="5" {{ $rawC2 == 5 ? 'selected' : '' }}>5 - Sangat Mudah</option>
                                    </select>
                                </td>
                                <td class="p-3 text-center">
                                    <select name="items[{{ $idx }}][values][{{ $criteria[2]->id }}]" class="w-28 px-2 py-1.5 rounded-lg border border-slate-300 font-mono text-xs focus:ring-2 focus:ring-brand-500 outline-none">
                                        <option value="1" {{ $rawC3 == 1 ? 'selected' : '' }}>1 - Sangat Sulit</option>
                                        <option value="2" {{ $rawC3 == 2 ? 'selected' : '' }}>2 - Sulit</option>
                                        <option value="3" {{ $rawC3 == 3 ? 'selected' : '' }}>3 - Sedang</option>
                                        <option value="4" {{ $rawC3 == 4 ? 'selected' : '' }}>4 - Mudah</option>
                                        <option value="5" {{ $rawC3 == 5 ? 'selected' : '' }}>5 - Sangat Mudah</option>
                                    </select>
                                </td>
                                <td class="p-3 text-center">
                                    <input type="number" min="5" max="300" step="5" name="items[{{ $idx }}][values][{{ $criteria[3]->id }}]" value="{{ $rawC4 }}" required
                                        class="w-24 text-center px-2 py-1.5 rounded-lg border border-slate-300 font-mono text-xs text-rose-700 font-bold focus:ring-2 focus:ring-brand-500 outline-none">
                                    <span class="text-[10px] text-slate-400">mnt</span>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-[10px] text-slate-400">Rp</span>
                                        <input type="number" min="500" max="50000" step="100" name="items[{{ $idx }}][values][{{ $criteria[4]->id }}]" value="{{ $rawC5 }}" required
                                            class="w-28 text-right px-2 py-1.5 rounded-lg border border-slate-300 font-mono text-xs font-bold text-emerald-700 focus:ring-2 focus:ring-brand-500 outline-none">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">Menyimpan 120 input penilaian operasional untuk kriteria C2 hingga C5.</span>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-extrabold bg-gradient-to-r from-brand-600 to-amber-600 hover:from-brand-700 hover:to-amber-700 text-white shadow-md transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan & Hitung Ulang SAW
                </button>
            </div>
        </div>
    </form>

</div>
@endsection

