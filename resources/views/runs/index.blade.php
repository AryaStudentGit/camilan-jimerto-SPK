@extends('layouts.app')

@section('title', 'Periode Evaluasi SPK - Camilan Jimerto')

@section('content')
<div class="space-y-6" x-data="{ createModal: false }">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Kelola Periode Evaluasi SPK</h1>
            <p class="text-xs text-slate-500 mt-1">
                Atur rentang periode pengamatan bulanan (default 2 bulan studi kasus Jimerto) dan pilih periode yang aktif di Dashboard.
            </p>
        </div>
        <button type="button" @click="createModal = true" class="px-4 py-2.5 rounded-xl text-xs font-extrabold bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Periode Evaluasi
        </button>
    </div>

    <!-- Table of Calculation Runs -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3.5">Status Dashboard</th>
                        <th class="p-3.5">Judul Evaluasi</th>
                        <th class="p-3.5">Rentang Periode Pengamatan</th>
                        <th class="p-3.5 text-center">Konsistensi AHP</th>
                        <th class="p-3.5 text-center">Data Produk Terhitung</th>
                        <th class="p-3.5">Dibuat Oleh</th>
                        <th class="p-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($runs as $r)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3.5">
                                @if($r->is_active_dashboard)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                        Aktif di Dashboard
                                    </span>
                                @else
                                    <form action="{{ route('runs.activate', $r->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 rounded-xl text-xs font-bold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 border border-slate-200 text-slate-600 transition">
                                            Aktifkan &rarr;
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="p-3.5">
                                <a href="{{ route('dashboard', ['run_id' => $r->id]) }}" class="font-bold text-slate-900 hover:underline hover:text-brand-600 text-sm">
                                    {{ $r->title }}
                                </a>
                                <span class="block text-[11px] text-slate-400 mt-0.5">ID: #RUN-{{ $r->id }}</span>
                            </td>
                            <td class="p-3.5 font-mono font-bold text-slate-700">
                                {{ $r->start_period }} s.d. {{ $r->end_period }}
                            </td>
                            <td class="p-3.5 text-center">
                                @if($r->is_consistent)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        CR = {{ number_format((float) $r->consistency_ratio, 4) }} (OK)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                        Inkonsisten (>0.10)
                                    </span>
                                @endif
                            </td>
                            <td class="p-3.5 text-center font-semibold text-slate-600">
                                {{ $r->saw_results_count }} peringkat (30 produk)
                            </td>
                            <td class="p-3.5 text-slate-600">
                                {{ $r->user?->name ?? 'System' }}
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('dashboard', ['run_id' => $r->id]) }}" class="p-1.5 rounded-lg text-brand-600 hover:bg-brand-50 font-bold" title="Buka Dashboard">
                                        Buka &rarr;
                                    </a>
                                    @if(!$r->is_active_dashboard)
                                        <form action="{{ route('runs.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Hapus periode evaluasi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">Belum ada periode evaluasi perhitungan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Create New Run -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="createModal = false"></div>
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 relative z-10 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Buat Periode Evaluasi Baru</h3>
                <button @click="createModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('runs.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Judul Evaluasi:</label>
                    <input type="text" name="title" required placeholder="Contoh: Evaluasi Produksi Periode Nov - Des 2026"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mulai Periode (YYYY-MM):</label>
                        <input type="text" name="start_period" required placeholder="2026-10" pattern="\d{4}-\d{2}"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 font-mono focus:ring-2 focus:ring-brand-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Akhir Periode (YYYY-MM):</label>
                        <input type="text" name="end_period" required placeholder="2026-11" pattern="\d{4}-\d{2}"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 font-mono focus:ring-2 focus:ring-brand-500 outline-none">
                    </div>
                </div>

                <p class="text-[11px] text-slate-500 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-200">
                    Sistem otomatis mengagregasi volume unit terjual dari tabel rekap bulanan pada rentang periode yang ditentukan untuk mengisi kriteria C1 dan menjalankan SAW.
                </p>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-600">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white shadow-sm">
                        Simpan & Sinkronkan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

