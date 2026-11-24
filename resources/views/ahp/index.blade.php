@extends('layouts.app')

@section('title', 'Kuesioner Perbandingan Berpasangan AHP - Camilan Jimerto')

@section('content')
<div class="space-y-6">

    <!-- Header & Period Info -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Kuesioner Perbandingan Berpasangan AHP</h1>
            <p class="text-xs text-slate-500 mt-1">
                Diisi oleh Pemilik UMKM untuk menentukan bobot kepentingan relatif antar 5 kriteria menggunakan Skala Saaty 1–9.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-xs">
                <span class="text-slate-500">Periode Aktif:</span>
                <strong class="text-slate-900 font-bold ml-1">{{ $run->title }}</strong>
            </div>
            @if($run->is_consistent)
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    Konsisten (CR = {{ number_format((float) $run->consistency_ratio, 4) }} &le; 0.10)
                </span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                    Inkonsisten (CR = {{ number_format((float) $run->consistency_ratio, 4) }} &gt; 0.10)
                </span>
            @endif
        </div>
    </div>

    <!-- Saaty Scale Reference Guide -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 p-4 rounded-2xl text-xs text-amber-900 space-y-2">
        <div class="font-extrabold flex items-center gap-1.5 text-amber-950">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Panduan Skala Perbandingan Saaty (1 – 9)
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-[11px] font-medium text-amber-900/90">
            <div class="p-2 rounded-lg bg-white/60 border border-amber-200"><strong>1:</strong> Sama penting</div>
            <div class="p-2 rounded-lg bg-white/60 border border-amber-200"><strong>3:</strong> Sedikit lebih penting</div>
            <div class="p-2 rounded-lg bg-white/60 border border-amber-200"><strong>5:</strong> Lebih penting</div>
            <div class="p-2 rounded-lg bg-white/60 border border-amber-200"><strong>7:</strong> Sangat penting</div>
            <div class="p-2 rounded-lg bg-white/60 border border-amber-200"><strong>9:</strong> Mutlak lebih penting</div>
        </div>
    </div>

    <!-- Questionnaire Form (10 Unique Pairs) -->
    <form action="{{ route('ahp.update', $run->id) }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">10 Pasangan Perbandingan Kriteria</h2>
                    <p class="text-xs text-slate-500">Pilih kriteria mana yang lebih diutamakan, lalu tentukan tingkat kepentingannya (1 s.d. 9).</p>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-extrabold bg-gradient-to-r from-brand-600 to-amber-600 hover:from-brand-700 hover:to-amber-700 text-white shadow-md transition">
                    Simpan & Hitung Bobot AHP
                </button>
            </div>

            <div class="space-y-4">
                @foreach($pairs as $idx => $p)
                    <div class="p-4 rounded-xl border border-slate-200 hover:border-brand-300 bg-slate-50/50 transition flex flex-col md:flex-row md:items-center justify-between gap-4"
                        x-data="{
                            dir: '{{ $p['direction'] }}',
                            scale: {{ $p['scale_value'] }}
                        }">
                        <input type="hidden" name="pairs[{{ $idx }}][c1_id]" value="{{ $p['criterion_1']->id }}">
                        <input type="hidden" name="pairs[{{ $idx }}][c2_id]" value="{{ $p['criterion_2']->id }}">

                        <!-- Left Criterion -->
                        <div class="w-full md:w-1/3 text-left">
                            <label class="cursor-pointer block p-2 rounded-lg transition" :class="dir === 'left' ? 'bg-brand-100 border border-brand-300' : 'hover:bg-slate-100'">
                                <input type="radio" name="pairs[{{ $idx }}][direction]" value="left" x-model="dir" class="sr-only">
                                <span class="text-xs font-bold text-slate-900 block" :class="dir === 'left' ? 'text-brand-800' : ''">
                                    {{ $p['criterion_1']->code }} - {{ $p['criterion_1']->name }}
                                </span>
                                <span class="text-[10px] text-slate-400 block">{{ $p['criterion_1']->attribute_type }}</span>
                            </label>
                        </div>

                        <!-- Scale Selector (Center) -->
                        <div class="w-full md:w-1/3 flex flex-col items-center justify-center space-y-1">
                            <div class="flex items-center gap-1">
                                <button type="button" @click="dir = 'equal'; scale = 1" class="text-[10px] px-2 py-0.5 rounded font-bold" :class="dir === 'equal' ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-600'">
                                    Sama (1)
                                </button>
                            </div>
                            <div class="flex items-center gap-2 w-full max-w-xs">
                                <span class="text-[10px] font-bold text-brand-600">&larr; C1</span>
                                <select name="pairs[{{ $idx }}][scale]" x-model.number="scale" class="w-full text-xs font-bold text-center bg-white border border-slate-300 rounded-lg py-1.5 focus:ring-2 focus:ring-brand-500 outline-none">
                                    <option value="1">1 - Sama Kuat / Penting</option>
                                    <option value="2">2 - Nilai Antara (1 & 3)</option>
                                    <option value="3">3 - Sedikit Lebih Penting</option>
                                    <option value="4">4 - Nilai Antara (3 & 5)</option>
                                    <option value="5">5 - Lebih Penting</option>
                                    <option value="6">6 - Nilai Antara (5 & 7)</option>
                                    <option value="7">7 - Sangat Penting</option>
                                    <option value="8">8 - Nilai Antara (7 & 9)</option>
                                    <option value="9">9 - Mutlak Lebih Penting</option>
                                </select>
                                <span class="text-[10px] font-bold text-amber-600">C2 &rarr;</span>
                            </div>
                        </div>

                        <!-- Right Criterion -->
                        <div class="w-full md:w-1/3 text-right">
                            <label class="cursor-pointer block p-2 rounded-lg transition" :class="dir === 'right' ? 'bg-amber-100 border border-amber-300' : 'hover:bg-slate-100'">
                                <input type="radio" name="pairs[{{ $idx }}][direction]" value="right" x-model="dir" class="sr-only">
                                <span class="text-xs font-bold text-slate-900 block" :class="dir === 'right' ? 'text-amber-800' : ''">
                                    {{ $p['criterion_2']->code }} - {{ $p['criterion_2']->name }}
                                </span>
                                <span class="text-[10px] text-slate-400 block">{{ $p['criterion_2']->attribute_type }}</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Submit Button Bar -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">Nilai resiprokal (1/a) otomatis terhitung kebalikan baris & kolom.</span>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-extrabold bg-gradient-to-r from-brand-600 to-amber-600 hover:from-brand-700 hover:to-amber-700 text-white shadow-md transition">
                    Simpan & Hitung Bobot AHP
                </button>
            </div>
        </div>
    </form>

    <!-- Current Resulting Weights Display -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Hasil Bobot Prioritas & Uji Konsistensi Saat Ini</h2>
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            @foreach($weights as $w)
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 text-center">
                    <span class="text-xs font-black text-brand-700 block">{{ $w->criterion->code }}</span>
                    <span class="text-xs font-semibold text-slate-700 block truncate">{{ $w->criterion->name }}</span>
                    <span class="text-lg font-black text-slate-900 block mt-1">{{ number_format($w->weight * 100, 2) }}%</span>
                    <span class="text-[10px] text-slate-400 font-mono">({{ $w->weight }})</span>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection

