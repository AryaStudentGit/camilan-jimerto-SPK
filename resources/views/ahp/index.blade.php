@extends('layouts.app')

@section('title', 'Kuesioner Perbandingan Berpasangan AHP - Camilan Jimerto')

@section('content')
<div class="space-y-6">

    <!-- Header & Period Info -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Kuesioner Perbandingan Berpasangan AHP</h1>
            <p class="text-xs text-slate-500 mt-1">
                Diisi oleh Pemilik UMKM untuk menentukan bobot kepentingan relatif antar 5 kriteria menggunakan Skala Saaty 1&ndash;9.
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
            Panduan Skala Perbandingan Saaty (1 &ndash; 9)
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
                    <p class="text-xs text-slate-500">Klik kriteria mana yang lebih penting, lalu tentukan tingkat kepentingannya (1 s.d. 9).</p>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-extrabold bg-gradient-to-r from-brand-600 to-amber-600 hover:from-brand-700 hover:to-amber-700 text-white shadow-md transition">
                    Simpan &amp; Hitung Bobot AHP
                </button>
            </div>

            <div class="space-y-4">
                @foreach($pairs as $idx => $p)
                    @php
                        $initDir   = $p['direction'];   // 'left' | 'right'
                        $initScale = $p['scale_value']; // int 1-9
                    @endphp

                    {{--
                        Alpine.js x-data initialized from PHP data-* attributes.
                        This avoids all string-escaping / JSON-encoding issues inside HTML attributes.
                        dir  : 'left' | 'right' | 'equal'
                        scale: integer 1-9
                    --}}
                    <div class="pair-row p-4 rounded-xl border border-slate-200 hover:border-brand-300 bg-slate-50/50 transition flex flex-col md:flex-row md:items-center justify-between gap-4"
                         x-data="ahpPair('{{ $initDir }}', {{ $initScale }})"
                         data-idx="{{ $idx }}">

                        <!-- Hidden inputs that always carry the current direction & scale to the server -->
                        <input type="hidden" name="pairs[{{ $idx }}][c1_id]"    value="{{ $p['criterion_1']->id }}">
                        <input type="hidden" name="pairs[{{ $idx }}][c2_id]"    value="{{ $p['criterion_2']->id }}">
                        <input type="hidden" name="pairs[{{ $idx }}][direction]" :value="dir">
                        <input type="hidden" name="pairs[{{ $idx }}][scale]"    :value="scale">

                        <!-- Left Criterion (Brand / Blue) -->
                        <div class="w-full md:w-1/3 text-left">
                            <button type="button"
                                    @click="pickLeft()"
                                    class="w-full text-left p-2.5 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-brand-400"
                                    :class="dir === 'left'
                                        ? 'bg-brand-100 border-brand-400 shadow-xs'
                                        : 'bg-white border-slate-200 hover:bg-slate-50'">
                                <span class="text-xs font-bold block"
                                      :class="dir === 'left' ? 'text-brand-800' : 'text-slate-700'">
                                    {{ $p['criterion_1']->code }} &ndash; {{ $p['criterion_1']->name }}
                                </span>
                                <span class="text-[10px] block mt-0.5"
                                      :class="dir === 'left' ? 'text-brand-600' : 'text-slate-400'">
                                    {{ $p['criterion_1']->attribute_type }}
                                </span>
                            </button>
                        </div>

                        <!-- Scale Selector (Center) -->
                        <div class="w-full md:w-1/3 flex flex-col items-center justify-center gap-1.5">
                            <!-- Sama button -->
                            <button type="button"
                                    @click="pickEqual()"
                                    class="text-[10px] px-3 py-1 rounded-full font-bold border transition focus:outline-none focus:ring-2 focus:ring-slate-400"
                                    :class="dir === 'equal'
                                        ? 'bg-slate-800 text-white border-slate-800'
                                        : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-100'">
                                Sama (1)
                            </button>

                            <!-- Direction arrows + scale select -->
                            <div class="flex items-center gap-2 w-full max-w-xs">
                                <span class="text-[10px] font-bold text-brand-600 shrink-0">&larr;</span>
                                <select name="_scale_display_{{ $idx }}"
                                        x-model="scale"
                                        @change="onScaleChange($event)"
                                        class="w-full text-xs font-bold text-center bg-white border border-slate-300 rounded-lg py-1.5 focus:ring-2 focus:ring-brand-500 outline-none">
                                    <option value="1">1 &ndash; Sama Kuat / Penting</option>
                                    <option value="2">2 &ndash; Nilai Antara (1 &amp; 3)</option>
                                    <option value="3">3 &ndash; Sedikit Lebih Penting</option>
                                    <option value="4">4 &ndash; Nilai Antara (3 &amp; 5)</option>
                                    <option value="5">5 &ndash; Lebih Penting</option>
                                    <option value="6">6 &ndash; Nilai Antara (5 &amp; 7)</option>
                                    <option value="7">7 &ndash; Sangat Penting</option>
                                    <option value="8">8 &ndash; Nilai Antara (7 &amp; 9)</option>
                                    <option value="9">9 &ndash; Mutlak Lebih Penting</option>
                                </select>
                                <span class="text-[10px] font-bold text-amber-600 shrink-0">&rarr;</span>
                            </div>

                            <!-- Visual label: which side is favored -->
                            <div class="text-[10px] font-semibold text-center"
                                 :class="dir === 'equal' ? 'text-slate-500' : (dir === 'left' ? 'text-brand-600' : 'text-amber-600')">
                                <span x-text="dir === 'equal' ? 'Sama pentingnya' : (dir === 'left' ? '&#8592; Lebih diutamakan' : 'Lebih diutamakan &#8594;')"></span>
                            </div>
                        </div>

                        <!-- Right Criterion (Amber / Orange) -->
                        <div class="w-full md:w-1/3 text-right">
                            <button type="button"
                                    @click="pickRight()"
                                    class="w-full text-right p-2.5 rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-amber-400"
                                    :class="dir === 'right'
                                        ? 'bg-amber-100 border-amber-400 shadow-xs'
                                        : 'bg-white border-slate-200 hover:bg-slate-50'">
                                <span class="text-xs font-bold block"
                                      :class="dir === 'right' ? 'text-amber-800' : 'text-slate-700'">
                                    {{ $p['criterion_2']->code }} &ndash; {{ $p['criterion_2']->name }}
                                </span>
                                <span class="text-[10px] block mt-0.5"
                                      :class="dir === 'right' ? 'text-amber-600' : 'text-slate-400'">
                                    {{ $p['criterion_2']->attribute_type }}
                                </span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Submit Button Bar -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">Nilai resiprokal (1/a) otomatis terhitung kebalikan baris &amp; kolom.</span>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-extrabold bg-gradient-to-r from-brand-600 to-amber-600 hover:from-brand-700 hover:to-amber-700 text-white shadow-md transition">
                    Simpan &amp; Hitung Bobot AHP
                </button>
            </div>
        </div>
    </form>

    <!-- Current Resulting Weights Display -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Hasil Bobot Prioritas &amp; Uji Konsistensi Saat Ini</h2>
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

@push('scripts')
<script>
/**
 * Alpine.js component for each AHP pairwise comparison row.
 * Initialized from plain PHP parameters - no JSON encoding or escaping needed.
 *
 * @param {string} initDir   - Initial direction: 'left' | 'right' | 'equal'
 * @param {number} initScale - Initial scale: integer 1-9
 */
function ahpPair(initDir, initScale) {
    return {
        dir:   initDir   || 'equal',
        scale: parseInt(initScale) || 1,

        /** User clicked the left (brand) criterion button */
        pickLeft() {
            this.dir = 'left';
            if (this.scale < 2) this.scale = 2; // ensure visual feedback (not equal)
        },

        /** User clicked the right (amber) criterion button */
        pickRight() {
            this.dir = 'right';
            if (this.scale < 2) this.scale = 2;
        },

        /** User clicked the "Sama (1)" button */
        pickEqual() {
            this.dir   = 'equal';
            this.scale = 1;
        },

        /** Scale dropdown changed - infer direction if currently equal */
        onScaleChange(event) {
            const newScale = parseInt(event.target.value) || 1;
            this.scale = newScale;
            if (newScale === 1) {
                this.dir = 'equal';
            } else if (this.dir === 'equal') {
                // User changed scale away from 1 while equal - default to left
                this.dir = 'left';
            }
        },
    };
}
</script>
@endpush
@endsection
