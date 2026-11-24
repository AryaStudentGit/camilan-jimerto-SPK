@extends('layouts.app')

@section('title', 'Dashboard SPK Prioritas Produksi')

@section('content')
<div class="space-y-6" x-data="dashboardHandler()">

    <!-- Header & Period Filter -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Dashboard SPK Prioritas Produksi</h1>
                @if($currentRun->is_active_dashboard)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                        Dashboard Utama
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Kombinasi Metode AHP (Penetapan Bobot Kriteria) & SAW (Pemeringkatan 30 Alternatif Produk)
            </p>
        </div>

        <!-- Period Selector & Action Shortcuts -->
        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500 uppercase">Periode:</label>
                <select name="run_id" onchange="this.form.submit()" class="text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 outline-none">
                    @foreach($runs as $r)
                        <option value="{{ $r->id }}" {{ $r->id == $currentRun->id ? 'selected' : '' }}>
                            {{ $r->title }} ({{ $r->start_period }} s.d. {{ $r->end_period }}) {{ $r->is_active_dashboard ? '★' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if(Auth::user()->isAdmin())
                <a href="{{ route('sales-recaps.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Update Rekap Penjualan
                </a>
            @endif

            <div class="text-[11px] text-slate-400 border-l border-slate-200 pl-3">
                Sinkronisasi: <span class="font-medium text-slate-600">{{ $currentRun->updated_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Top Product -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-700 text-white p-5 rounded-2xl shadow-md relative overflow-hidden">
            <div class="relative z-10">
                <div class="text-xs font-semibold uppercase tracking-wider text-emerald-100">Produk Prioritas #1</div>
                <div class="text-xl font-extrabold mt-1 truncate">{{ $topProduct?->product->name ?? 'Belum ada data' }}</div>
                <div class="mt-2 flex items-center justify-between text-xs text-emerald-100">
                    <span>Skor Preferensi: <strong class="text-white text-sm font-black">{{ $topProduct?->final_score ?? '0.0000' }}</strong></span>
                    <span class="bg-white/20 px-2 py-0.5 rounded-full font-bold uppercase text-[10px]">{{ $topProduct?->product->code ?? 'A?' }}</span>
                </div>
            </div>
            <div class="absolute -right-3 -bottom-3 text-white/10 font-black text-7xl select-none">#1</div>
        </div>

        <!-- Card 2: Total Demand Active Period -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Volume Permintaan</div>
                <div class="text-2xl font-black text-slate-900 mt-1">
                    {{ number_format($totalDemandUnits, 0, ',', '.') }} <span class="text-sm font-semibold text-slate-500">unit</span>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span>Periode {{ $currentRun->start_period }} s.d. {{ $currentRun->end_period }}</span>
            </div>
        </div>

        <!-- Card 3: AHP Consistency Status -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Konsistensi AHP</div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-2xl font-black {{ $currentRun->is_consistent ? 'text-emerald-600' : 'text-rose-600' }}">
                        CR = {{ number_format((float) $currentRun->consistency_ratio, 4) }}
                    </span>
                    @if($currentRun->is_consistent)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            Konsisten (≤0.10)
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                            Perlu Revisi (>0.10)
                        </span>
                    @endif
                </div>
            </div>
            <div class="mt-2 text-xs text-slate-500">
                λ maks: <strong class="text-slate-700">{{ number_format((float) $currentRun->lambda_max, 4) }}</strong> • CI: <strong class="text-slate-700">{{ number_format((float) $currentRun->consistency_index, 4) }}</strong>
            </div>
        </div>

        <!-- Card 4: Most Influential Criterion -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kriteria Terbobot Tertinggi</div>
                <div class="text-lg font-black text-slate-900 mt-1 truncate">
                    {{ $mostInfluentialCrit?->name ?? '-' }} ({{ $mostInfluentialCrit?->code }})
                </div>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                <span>Bobot AHP: <strong class="text-brand-600 font-bold">{{ number_format($maxWeight * 100, 1) }}%</strong></span>
                <span class="text-[10px] uppercase font-bold text-slate-400">{{ $mostInfluentialCrit?->attribute_type }}</span>
            </div>
        </div>
    </div>

    <!-- Tier Cluster Badges Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-black flex items-center justify-center text-xs">
                    {{ $tierCounts['utama'] }}
                </div>
                <div>
                    <div class="text-xs font-bold text-emerald-950">Prioritas Utama (Rank 1 - 10)</div>
                    <div class="text-[11px] text-emerald-700">Produksi rutin harian/mingguan (Anti-Stockout)</div>
                </div>
            </div>
        </div>
        <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-500 text-white font-black flex items-center justify-center text-xs">
                    {{ $tierCounts['menengah'] }}
                </div>
                <div>
                    <div class="text-xs font-bold text-amber-950">Prioritas Menengah (Rank 11 - 20)</div>
                    <div class="text-[11px] text-amber-700">Diproduksi dengan kapasitas mesin & pekerja sisa</div>
                </div>
            </div>
        </div>
        <div class="p-3.5 rounded-xl bg-rose-50/70 border border-rose-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-500 text-white font-black flex items-center justify-center text-xs">
                    {{ $tierCounts['rendah'] }}
                </div>
                <div>
                    <div class="text-xs font-bold text-rose-950">Prioritas Rendah (Rank 21 - 30)</div>
                    <div class="text-[11px] text-rose-700">Make-to-Order / diproduksi saat ada pesanan nota khusus</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Analytics Section: Chart & AHP Weights side by side -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Horizontal Bar Chart: 30 Products -->
        <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Grafik Peringkat Skor SAW (30 Produk)</h2>
                    <p class="text-xs text-slate-500">Klik batang produk untuk membuka analisis mendalam (Drill-Down Level 2)</p>
                </div>
                <span class="text-xs bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg font-medium">Interaktif ApexCharts</span>
            </div>
            <div id="rankingChart" class="flex-1 w-full min-h-[550px]"></div>
        </div>

        <!-- AHP Criteria Weights & Interactive Drill-Down -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Bobot Kriteria AHP</h2>
                    <a href="{{ route('ahp.index', ['run_id' => $currentRun->id]) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">
                        Ubah Bobot &rarr;
                    </a>
                </div>
                <p class="text-xs text-slate-500 mb-3">Klik kriteria pada chart untuk melihat distribusi 30 produk pada kriteria tersebut</p>
                <div id="ahpChart" class="min-h-[260px]"></div>

                <!-- Criteria Table List -->
                <div class="space-y-2 mt-4">
                    @foreach($criteria as $c)
                        <button type="button" @click="openCriterionDrilldown({{ $c->id }})" class="w-full text-left p-2.5 rounded-xl border border-slate-200 hover:border-brand-500 hover:bg-brand-50/40 transition flex items-center justify-between group">
                            <div>
                                <span class="text-xs font-bold text-slate-800 group-hover:text-brand-700">{{ $c->code }} - {{ $c->name }}</span>
                                <div class="text-[10px] text-slate-400">{{ $c->attribute_type === 'benefit' ? 'Benefit (Makin tinggi makin baik)' : 'Cost (Makin rendah makin baik)' }}</div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-black text-slate-900">{{ number_format(($weights[$c->id] ?? 0) * 100, 1) }}%</span>
                                <span class="text-[10px] text-brand-600 font-semibold block group-hover:underline">Detail &rarr;</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- What-if quick trigger -->
            <div class="mt-5 pt-4 border-t border-slate-200">
                <button type="button" @click="simulatorOpen = !simulatorOpen" class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Buka Simulator Sensitivitas (What-If)
                </button>
            </div>
        </div>
    </div>

    <!-- What-If Sensitivity Simulator Drawer / Panel -->
    <div x-show="simulatorOpen" x-cloak class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-6 rounded-2xl shadow-xl border border-slate-700 transition space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-4 border-b border-slate-700">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                    <h3 class="text-base font-extrabold tracking-tight text-white">Simulator Sensitivitas Real-Time (What-If Analysis)</h3>
                </div>
                <p class="text-xs text-slate-300 mt-1">
                    Geser slider bobot di bawah ini untuk melihat simulasi perubahan peringkat produk secara langsung tanpa mengubah data database.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-300">Total Bobot:</span>
                <span class="text-sm font-black px-3 py-1 rounded-lg" :class="simTotalWeight === 100 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-rose-500/20 text-rose-400 border border-rose-500/40'" x-text="simTotalWeight + '%'"></span>
                <button @click="resetSimulatorWeights()" class="text-xs px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 font-semibold transition">
                    Reset Bobot
                </button>
                <button @click="simulatorOpen = false" class="text-slate-400 hover:text-white p-1.5">&times;</button>
            </div>
        </div>

        <!-- Sliders Grid for C1 - C5 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach($criteria as $c)
                <div class="bg-slate-800/80 p-3.5 rounded-xl border border-slate-700">
                    <div class="flex justify-between items-center text-xs mb-1.5">
                        <span class="font-bold text-amber-300">{{ $c->code }}</span>
                        <span class="font-black text-white text-sm" x-text="simWeights[{{ $c->id }}] + '%'"></span>
                    </div>
                    <div class="text-[11px] text-slate-300 font-medium truncate mb-2">{{ $c->name }}</div>
                    <input type="range" min="0" max="100" step="1"
                        x-model.number="simWeights[{{ $c->id }}]"
                        @input="handleWeightInput({{ $c->id }})"
                        class="w-full accent-amber-500 cursor-pointer">
                </div>
            @endforeach
        </div>

        <!-- Simulation Results Preview Table -->
        <div x-show="simulatedRankings.length > 0" class="pt-2">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-300 mb-2 flex items-center justify-between">
                <span>Hasil Simulasi Pemeringkatan Baru (Top 10)</span>
                <span class="text-[11px] text-slate-400 font-normal">Perubahan posisi dibanding ranking dasar</span>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-700">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800 text-slate-300 uppercase font-semibold">
                        <tr>
                            <th class="p-2.5">Simulasi</th>
                            <th class="p-2.5">Dasar</th>
                            <th class="p-2.5">Pergeseran</th>
                            <th class="p-2.5">Produk</th>
                            <th class="p-2.5">Skor Baru</th>
                            <th class="p-2.5">Klaster Baru</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700 bg-slate-800/40">
                        <template x-for="item in simulatedRankings.slice(0, 10)" :key="item.product_id">
                            <tr>
                                <td class="p-2.5 font-black text-amber-400" x-text="'#' + item.rank"></td>
                                <td class="p-2.5 text-slate-400" x-text="'#' + item.old_rank"></td>
                                <td class="p-2.5 font-bold">
                                    <span x-show="item.rank_diff > 0" class="text-emerald-400 flex items-center gap-0.5">
                                        &uarr; <span x-text="'+' + item.rank_diff"></span>
                                    </span>
                                    <span x-show="item.rank_diff < 0" class="text-rose-400 flex items-center gap-0.5">
                                        &darr; <span x-text="item.rank_diff"></span>
                                    </span>
                                    <span x-show="item.rank_diff === 0" class="text-slate-400">=</span>
                                </td>
                                <td class="p-2.5 font-bold text-white" x-text="item.product_name + ' (' + item.product_code + ')'"></td>
                                <td class="p-2.5 font-mono text-slate-200" x-text="item.final_score"></td>
                                <td class="p-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                        :class="item.priority_tier === 'utama' ? 'bg-emerald-500/20 text-emerald-300' : (item.priority_tier === 'menengah' ? 'bg-amber-500/20 text-amber-300' : 'bg-rose-500/20 text-rose-300')"
                                        x-text="item.priority_tier"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Peringkat SAW 30 Produk Table Section -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Tabel Lengkap Peringkat & Rekomendasi Produksi</h2>
                <p class="text-xs text-slate-500">Pilih baris produk untuk membuka drill-down, atau centang 2-3 produk untuk komparasi head-to-head</p>
            </div>

            <!-- Filters & Comparison Button -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Cluster Filter -->
                <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-semibold">
                    <button type="button" @click="clusterFilter = 'all'" :class="clusterFilter === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'" class="px-3 py-1.5 rounded-lg transition">
                        Semua (30)
                    </button>
                    <button type="button" @click="clusterFilter = 'utama'" :class="clusterFilter === 'utama' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600'" class="px-3 py-1.5 rounded-lg transition">
                        Utama (10)
                    </button>
                    <button type="button" @click="clusterFilter = 'menengah'" :class="clusterFilter === 'menengah' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600'" class="px-3 py-1.5 rounded-lg transition">
                        Menengah (10)
                    </button>
                    <button type="button" @click="clusterFilter = 'rendah'" :class="clusterFilter === 'rendah' ? 'bg-rose-500 text-white shadow-xs' : 'text-slate-600'" class="px-3 py-1.5 rounded-lg transition">
                        Rendah (10)
                    </button>
                </div>

                <!-- Compare Button -->
                <button type="button" @click="openHeadToHead()" :disabled="selectedProducts.length < 2"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs"
                    :class="selectedProducts.length >= 2 ? 'bg-brand-600 text-white hover:bg-brand-700 cursor-pointer' : 'bg-slate-100 text-slate-400 cursor-not-allowed'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Bandingkan (<span x-text="selectedProducts.length"></span>)
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3 w-10 text-center">Pilih</th>
                        <th class="p-3 w-16">Peringkat</th>
                        <th class="p-3 w-20">Kode</th>
                        <th class="p-3">Nama Alternatif Produk</th>
                        <th class="p-3 text-right">C1 (Permintaan)</th>
                        <th class="p-3 text-right">C2 (Bahan)</th>
                        <th class="p-3 text-right">C3 (Mudah)</th>
                        <th class="p-3 text-right">C4 (Waktu)</th>
                        <th class="p-3 text-right">C5 (Margin)</th>
                        <th class="p-3 text-right">Skor SAW (Vi)</th>
                        <th class="p-3 text-center">Klaster Produksi</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($sawResults as $res)
                        @php
                            $pId = $res->product_id;
                            $pAssess = $assessments->get($pId);
                            $rawC1 = $pAssess?->firstWhere('criterion_id', $criteria[0]->id)?->raw_value ?? 0;
                            $rawC2 = $pAssess?->firstWhere('criterion_id', $criteria[1]->id)?->raw_value ?? 0;
                            $rawC3 = $pAssess?->firstWhere('criterion_id', $criteria[2]->id)?->raw_value ?? 0;
                            $rawC4 = $pAssess?->firstWhere('criterion_id', $criteria[3]->id)?->raw_value ?? 0;
                            $rawC5 = $pAssess?->firstWhere('criterion_id', $criteria[4]->id)?->raw_value ?? 0;
                        @endphp
                        <tr class="hover:bg-brand-50/40 transition cursor-pointer"
                            x-show="clusterFilter === 'all' || clusterFilter === '{{ $res->priority_tier }}'">
                            <td class="p-3 text-center" @click.stop>
                                <input type="checkbox" :value="{{ $res->product_id }}" x-model="selectedProducts"
                                    :disabled="selectedProducts.length >= 3 && !selectedProducts.includes({{ $res->product_id }})"
                                    class="rounded text-brand-600 focus:ring-brand-500">
                            </td>
                            <td class="p-3">
                                <span class="w-6 h-6 rounded-full inline-flex items-center justify-center font-bold text-xs {{ $res->rank <= 3 ? 'bg-amber-100 text-amber-800 font-black' : 'text-slate-600' }}">
                                    #{{ $res->rank }}
                                </span>
                            </td>
                            <td class="p-3 font-bold text-slate-500">{{ $res->product->code }}</td>
                            <td class="p-3 font-bold text-slate-900" @click="openProductDrilldown({{ $res->product_id }})">
                                <span class="hover:underline text-brand-700">{{ $res->product->name }}</span>
                            </td>
                            <td class="p-3 text-right font-mono">{{ number_format($rawC1, 0, ',', '.') }} unit</td>
                            <td class="p-3 text-right font-mono">{{ $rawC2 }}/5</td>
                            <td class="p-3 text-right font-mono">{{ $rawC3 }}/5</td>
                            <td class="p-3 text-right font-mono">{{ $rawC4 }} mnt</td>
                            <td class="p-3 text-right font-mono">Rp{{ number_format($rawC5, 0, ',', '.') }}</td>
                            <td class="p-3 text-right font-mono font-black text-slate-900 text-sm">
                                {{ $res->final_score }}
                            </td>
                            <td class="p-3 text-center">
                                @if($res->priority_tier === 'utama')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        Prioritas Utama
                                    </span>
                                @elseif($res->priority_tier === 'menengah')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-amber-100 text-amber-800 border border-amber-300">
                                        Prioritas Menengah
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800 border border-rose-300">
                                        Prioritas Rendah
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-center" @click.stop>
                                <button type="button" @click="openProductDrilldown({{ $res->product_id }})" class="p-1.5 rounded-lg text-brand-600 hover:bg-brand-100 font-bold text-xs transition inline-flex items-center gap-1">
                                    <span>Drill-down</span>
                                    &rarr;
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Slide-Over Drawer: Product Drilldown (Level 2) -->
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="drawerOpen = false"></div>
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-2xl bg-white shadow-2xl flex flex-col justify-between overflow-y-auto">
                <!-- Drawer Header -->
                <div class="p-6 border-b border-slate-200 bg-slate-50 sticky top-0 z-20">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-black bg-brand-100 text-brand-800" x-text="drillProduct.code"></span>
                                <h3 class="text-xl font-extrabold text-slate-900" x-text="drillProduct.name"></h3>
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="drillRanking.tier_badge" x-text="drillRanking.tier_label"></span>
                                <span class="text-xs font-bold text-slate-600">Peringkat #<span x-text="drillRanking.rank"></span></span>
                                <span class="text-xs text-slate-400">• Skor Vi: <strong class="text-slate-800 font-black" x-text="drillRanking.final_score"></strong></span>
                            </div>
                        </div>
                        <button @click="drawerOpen = false" class="p-2 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-200 text-lg leading-none">&times;</button>
                    </div>
                </div>

                <!-- Drawer Body -->
                <div class="p-6 space-y-6 flex-1">
                    <!-- Narrative Explanation Box -->
                    <div class="p-4 rounded-xl bg-gradient-to-r from-brand-50 to-amber-50 border border-brand-200 text-xs text-slate-800 leading-relaxed space-y-1">
                        <div class="font-extrabold text-brand-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Analisis Rekomendasi Keputusan Produksi
                        </div>
                        <p x-text="drillRanking.narrative" class="font-medium text-slate-700"></p>
                    </div>

                    <!-- Mini Sales Trend Chart for C1 Formation -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Rincian Penjualan Bulanan (Pembentuk C1)</h4>
                                <p class="text-[11px] text-slate-500">Berdasarkan data tabel rekap penjualan bulanan</p>
                            </div>
                            <span class="text-xs font-black text-brand-600" x-text="'Total: ' + drillMonthlySales.reduce((acc, i) => acc + i.units, 0) + ' unit'"></span>
                        </div>
                        <div id="drillSalesChart" class="h-44 w-full"></div>
                    </div>

                    <!-- Radar & Stacked Contribution Charts -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 mb-2">Radar Kinerja Normalisasi</h4>
                            <div id="drillRadarChart" class="h-52 w-full"></div>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 mb-2">Kontribusi Terbobot (wj × rij)</h4>
                            <div id="drillBarChart" class="h-52 w-full"></div>
                        </div>
                    </div>

                    <!-- Calculation Table Breakdown -->
                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 mb-2">Tabel Dekomposisi Perhitungan SAW</h4>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="p-2">Kriteria</th>
                                        <th class="p-2 text-right">Nilai Mentah (xij)</th>
                                        <th class="p-2 text-right">Normalisasi (rij)</th>
                                        <th class="p-2 text-right">Bobot (wj)</th>
                                        <th class="p-2 text-right">Kontribusi</th>
                                        <th class="p-2 text-right">Porsi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="row in drillBreakdown" :key="row.criterion_id">
                                        <tr class="hover:bg-slate-50">
                                            <td class="p-2">
                                                <strong x-text="row.criterion_code" class="text-brand-600"></strong> - <span x-text="row.criterion_name"></span>
                                                <span class="block text-[10px] text-slate-400" x-text="row.attribute_type + ' • ' + row.unit_or_scale"></span>
                                            </td>
                                            <td class="p-2 text-right font-mono" x-text="row.raw_value"></td>
                                            <td class="p-2 text-right font-mono font-semibold" x-text="row.normalized_value"></td>
                                            <td class="p-2 text-right font-mono text-slate-500" x-text="row.weight"></td>
                                            <td class="p-2 text-right font-mono font-bold text-slate-900" x-text="row.weighted_contribution"></td>
                                            <td class="p-2 text-right font-mono text-brand-700 font-black" x-text="row.percentage_contribution + '%'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Drawer Footer -->
                <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Evaluasi Periode: <strong x-text="drillEvaluationPeriod"></strong></span>
                    <button @click="drawerOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Criterion Drilldown -->
    <div x-show="criterionModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="criterionModalOpen = false"></div>
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 relative z-10 max-h-[85vh] flex flex-col space-y-4">
            <div class="flex items-start justify-between border-b border-slate-200 pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-md bg-brand-100 text-brand-800 text-xs font-black" x-text="selectedCriterion.code"></span>
                        <h3 class="text-base font-extrabold text-slate-900" x-text="selectedCriterion.name"></h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Distribusi & peringkat 30 produk khusus pada kriteria ini (Bobot AHP: <strong x-text="(selectedCriterionWeight * 100).toFixed(1) + '%'"></strong>)
                    </p>
                </div>
                <button @click="criterionModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <div class="overflow-y-auto flex-1 border border-slate-200 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-bold sticky top-0">
                        <tr>
                            <th class="p-2.5 w-12 text-center">Urutan</th>
                            <th class="p-2.5">Produk</th>
                            <th class="p-2.5 text-right">Nilai Mentah</th>
                            <th class="p-2.5 text-right">Nilai Normalisasi (rij)</th>
                            <th class="p-2.5 text-right">Skor Terbobot</th>
                            <th class="p-2.5 text-center">Peringkat Global</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <template x-for="(item, idx) in criterionProducts" :key="item.product_id">
                            <tr class="hover:bg-slate-50">
                                <td class="p-2.5 text-center font-bold text-slate-500" x-text="'#' + (idx + 1)"></td>
                                <td class="p-2.5 font-bold text-slate-900" x-text="item.product_name + ' (' + item.product_code + ')'"></td>
                                <td class="p-2.5 text-right font-mono font-bold" x-text="item.raw_value"></td>
                                <td class="p-2.5 text-right font-mono" x-text="item.normalized_value"></td>
                                <td class="p-2.5 text-right font-mono font-black text-brand-600" x-text="item.weighted_value"></td>
                                <td class="p-2.5 text-center text-slate-500" x-text="'Rank #' + item.overall_rank"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="text-right">
                <button @click="criterionModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Head-to-Head Comparison -->
    <div x-show="comparisonModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="comparisonModalOpen = false"></div>
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full p-6 relative z-10 max-h-[90vh] flex flex-col space-y-4">
            <div class="flex items-start justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Perbandingan Produk Berdampingan (Head-to-Head)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Membandingkan <span x-text="selectedProducts.length"></span> produk terpilih untuk pertimbangan alokasi kapasitas produksi</p>
                </div>
                <button @click="comparisonModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <div class="overflow-y-auto flex-1 space-y-4">
                <div id="compareRadarChart" class="h-64 w-full"></div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold">
                            <tr>
                                <th class="p-3">Metrik / Kriteria</th>
                                <template x-for="p in comparedProductsData" :key="p.product.id">
                                    <th class="p-3 text-center" :class="'bg-slate-100 font-extrabold'">
                                        <span x-text="p.product.name"></span>
                                        <span class="block text-[10px] text-slate-500" x-text="'(Rank #' + p.ranking.rank + ')'"></span>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr>
                                <td class="p-3 font-bold text-slate-800">Skor Preferensi Vi</td>
                                <template x-for="p in comparedProductsData" :key="'vi_' + p.product.id">
                                    <td class="p-3 text-center font-mono font-black text-sm text-brand-600" x-text="p.ranking.final_score"></td>
                                </template>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold text-slate-800">Klaster Prioritas</td>
                                <template x-for="p in comparedProductsData" :key="'tier_' + p.product.id">
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase" :class="p.ranking.tier_badge" x-text="p.ranking.priority_tier"></span>
                                    </td>
                                </template>
                            </tr>
                            @foreach($criteria as $crit)
                                <tr>
                                    <td class="p-3">
                                        <strong class="text-brand-700">{{ $crit->code }}</strong> - {{ $crit->name }}
                                        <span class="text-[10px] text-slate-400 block">{{ $crit->unit_or_scale }}</span>
                                    </td>
                                    <template x-for="p in comparedProductsData" :key="'crit_{{ $crit->id }}_' + p.product.id">
                                        <td class="p-3 text-center font-mono">
                                            <span class="font-bold text-slate-900" x-text="p.breakdown.find(b => b.criterion_id === {{ $crit->id }})?.raw_value"></span>
                                            <span class="text-[10px] text-slate-500 block" x-text="'rij: ' + p.breakdown.find(b => b.criterion_id === {{ $crit->id }})?.normalized_value"></span>
                                        </td>
                                    </template>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-right border-t border-slate-200 pt-3">
                <button @click="comparisonModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboardHandler() {
    return {
        clusterFilter: 'all',
        selectedProducts: [],
        drawerOpen: false,
        criterionModalOpen: false,
        comparisonModalOpen: false,
        simulatorOpen: false,

        // Drill-down data holders
        drillProduct: {},
        drillRanking: {},
        drillBreakdown: [],
        drillMonthlySales: [],
        drillEvaluationPeriod: '',

        // Criterion Drilldown
        selectedCriterion: {},
        selectedCriterionWeight: 0,
        criterionProducts: [],

        // Comparison Data
        comparedProductsData: [],

        // Simulator Weights: initialize with current database weights in %
        simWeights: {
            @foreach($criteria as $c)
                {{ $c->id }}: {{ round(($weights[$c->id] ?? 0.2) * 100) }},
            @endforeach
        },
        simulatedRankings: [],

        get simTotalWeight() {
            let sum = 0;
            for (let k in this.simWeights) {
                sum += Number(this.simWeights[k] || 0);
            }
            return sum;
        },

        init() {
            this.initRankingChart();
            this.initAhpChart();
            this.runSimulation();
        },

        // 1. Horizontal Bar Chart for 30 Products
        initRankingChart() {
            const categories = @json($chartCategories);
            const scores = @json($chartScores);
            const productIds = @json($chartProductIds);
            const tiers = @json($chartTiers);

            const colors = tiers.map(t => {
                if (t === 'utama') return '#10b981'; // emerald
                if (t === 'menengah') return '#f59e0b'; // amber
                return '#f43f5e'; // rose
            });

            const options = {
                series: [{
                    name: 'Skor Preferensi (Vi)',
                    data: scores
                }],
                chart: {
                    type: 'bar',
                    height: 650,
                    toolbar: { show: true },
                    events: {
                        dataPointSelection: (event, chartContext, config) => {
                            const pId = productIds[config.dataPointIndex];
                            if (pId) {
                                this.openProductDrilldown(pId);
                            }
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                        distributed: true,
                        barHeight: '75%',
                        dataLabels: { position: 'bottom' }
                    }
                },
                colors: colors,
                dataLabels: {
                    enabled: true,
                    textAnchor: 'start',
                    style: { colors: ['#1e293b'], fontSize: '11px', fontFamily: 'Plus Jakarta Sans' },
                    formatter: function (val, opt) {
                        return '#' + (opt.dataPointIndex + 1) + '  ' + opt.w.globals.labels[opt.dataPointIndex] + ': ' + val.toFixed(4);
                    },
                    offsetX: 5,
                },
                xaxis: {
                    categories: categories,
                    min: 0,
                    max: 1.0,
                    labels: {
                        style: { fontSize: '11px', fontFamily: 'Plus Jakarta Sans' }
                    }
                },
                yaxis: {
                    labels: { show: false } // Hide left labels as they are embedded in bars
                },
                legend: { show: false },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function (val, opt) {
                            const tier = tiers[opt.dataPointIndex];
                            return val.toFixed(6) + ' [' + tier.toUpperCase() + '] - Klik untuk Drill-Down';
                        }
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#rankingChart"), options);
            chart.render();
        },

        // 2. AHP Weights Donut / Pie Chart
        initAhpChart() {
            const labels = @json($ahpChartLabels);
            const values = @json($ahpChartValues);
            const critIds = @json($ahpChartIds);

            const options = {
                series: values,
                labels: labels,
                chart: {
                    type: 'donut',
                    height: 250,
                    events: {
                        dataPointSelection: (event, chartContext, config) => {
                            const cId = critIds[config.dataPointIndex];
                            if (cId) {
                                this.openCriterionDrilldown(cId);
                            }
                        }
                    }
                },
                colors: ['#0284c7', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                legend: { position: 'bottom', fontSize: '11px', fontFamily: 'Plus Jakarta Sans' },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(1) + "%";
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(1) + "% (Klik untuk lihat 30 produk)";
                        }
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#ahpChart"), options);
            chart.render();
        },

        // Open Drill-Down Level 2 Drawer
        async openProductDrilldown(productId) {
            try {
                const res = await fetch(`/api/drilldown/${productId}?run_id={{ $currentRun->id }}`);
                const data = await res.json();

                this.drillProduct = data.product;
                this.drillRanking = data.ranking;
                this.drillBreakdown = data.breakdown;
                this.drillMonthlySales = data.monthly_sales;
                this.drillEvaluationPeriod = data.evaluation_period;
                this.drawerOpen = true;

                this.$nextTick(() => {
                    this.renderDrillSalesChart(data.monthly_sales);
                    this.renderDrillRadarChart(data.radar_labels, data.radar_values);
                    this.renderDrillBarChart(data.breakdown);
                });
            } catch (err) {
                alert('Gagal memuat detail drill-down produk.');
            }
        },

        // Mini Monthly Sales Trend Chart
        renderDrillSalesChart(sales) {
            const el = document.querySelector("#drillSalesChart");
            el.innerHTML = '';
            const options = {
                series: [{
                    name: 'Unit Terjual',
                    data: sales.map(s => s.units)
                }],
                chart: { type: 'bar', height: 160, toolbar: { show: false } },
                colors: ['#0284c7'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                dataLabels: { enabled: true, style: { fontSize: '10px' } },
                xaxis: {
                    categories: sales.map(s => s.period),
                    labels: { style: { fontSize: '10px' } }
                },
                yaxis: { labels: { style: { fontSize: '10px' } } }
            };
            new ApexCharts(el, options).render();
        },

        // Radar Chart for Product Normalization
        renderDrillRadarChart(labels, values) {
            const el = document.querySelector("#drillRadarChart");
            el.innerHTML = '';
            const options = {
                series: [{ name: 'Skor Normalisasi (%)', data: values }],
                chart: { height: 190, type: 'radar', toolbar: { show: false } },
                colors: ['#10b981'],
                xaxis: { categories: labels, labels: { style: { fontSize: '10px' } } },
                yaxis: { max: 100, min: 0, labels: { show: false } }
            };
            new ApexCharts(el, options).render();
        },

        // Stacked Contribution Chart for Product
        renderDrillBarChart(breakdown) {
            const el = document.querySelector("#drillBarChart");
            el.innerHTML = '';
            const options = {
                series: [{
                    name: 'Kontribusi Skor',
                    data: breakdown.map(b => b.weighted_contribution)
                }],
                chart: { height: 190, type: 'bar', toolbar: { show: false } },
                plotOptions: { bar: { borderRadius: 4, horizontal: true } },
                colors: ['#9e472a'],
                xaxis: {
                    categories: breakdown.map(b => b.criterion_code),
                    labels: { style: { fontSize: '10px' } }
                },
                dataLabels: {
                    enabled: true,
                    formatter: val => val.toFixed(3),
                    style: { fontSize: '10px' }
                }
            };
            new ApexCharts(el, options).render();
        },

        // Criterion Drilldown Modal
        async openCriterionDrilldown(criterionId) {
            try {
                const res = await fetch(`/api/criterion-drilldown/${criterionId}?run_id={{ $currentRun->id }}`);
                const data = await res.json();
                this.selectedCriterion = data.criterion;
                this.selectedCriterionWeight = data.weight;
                this.criterionProducts = data.products;
                this.criterionModalOpen = true;
            } catch (err) {
                alert('Gagal memuat data kriteria.');
            }
        },

        // Head-to-Head Comparison
        async openHeadToHead() {
            if (this.selectedProducts.length < 2) return;
            try {
                const list = [];
                for (let pId of this.selectedProducts) {
                    const res = await fetch(`/api/drilldown/${pId}?run_id={{ $currentRun->id }}`);
                    list.push(await res.json());
                }
                this.comparedProductsData = list;
                this.comparisonModalOpen = true;

                this.$nextTick(() => {
                    const el = document.querySelector("#compareRadarChart");
                    el.innerHTML = '';
                    const series = list.map(item => ({
                        name: item.product.name,
                        data: item.radar_values
                    }));
                    const options = {
                        series: series,
                        chart: { height: 250, type: 'radar', toolbar: { show: false } },
                        xaxis: { categories: list[0].radar_labels },
                        yaxis: { max: 100, min: 0 }
                    };
                    new ApexCharts(el, options).render();
                });
            } catch (err) {
                alert('Gagal memuat data perbandingan.');
            }
        },

        // What-If Simulator logic
        handleWeightInput(activeCritId) {
            // Auto balance: when one slider moves, adjust other sliders proportionally
            this.runSimulation();
        },

        resetSimulatorWeights() {
            this.simWeights = {
                @foreach($criteria as $c)
                    {{ $c->id }}: {{ round(($weights[$c->id] ?? 0.2) * 100) }},
                @endforeach
            };
            this.runSimulation();
        },

        async runSimulation() {
            try {
                const res = await fetch('{{ route("api.simulate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        run_id: {{ $currentRun->id }},
                        weights: this.simWeights
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.simulatedRankings = data.rankings;
                }
            } catch (e) {
                console.error(e);
            }
        }
    };
}
</script>
@endpush

