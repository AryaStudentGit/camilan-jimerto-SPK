@extends('layouts.app')

@section('title', 'Master Data Kriteria Penilaian - Camilan Jimerto')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Master Data 5 Kriteria Penilaian</h1>
            <p class="text-xs text-slate-500 mt-1">
                Kriteria pendukung keputusan penentuan prioritas produksi (C1 s.d. C5) dengan klasifikasi tipe atribut benefit atau cost.
            </p>
        </div>
        <div class="text-xs font-bold bg-amber-50 text-amber-800 px-3 py-1.5 rounded-xl border border-amber-200">
            5 Kriteria Evaluasi
        </div>
    </div>

    <!-- Criteria Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($criteria as $c)
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between space-y-3"
                x-data="{ editing: false, name: '{{ $c->name }}', type: '{{ $c->attribute_type }}', unit: '{{ $c->unit_or_scale }}', desc: '{{ $c->description }}' }">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2.5 py-1 rounded-lg bg-brand-100 text-brand-800 font-black text-sm">{{ $c->code }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $c->attribute_type === 'benefit' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                            {{ $c->attribute_type }}
                        </span>
                    </div>

                    <div x-show="!editing">
                        <h3 class="font-extrabold text-slate-900 text-base">{{ $c->name }}</h3>
                        <p class="text-xs font-semibold text-brand-700 mt-0.5">{{ $c->unit_or_scale }}</p>
                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">{{ $c->description }}</p>
                    </div>

                    <form x-show="editing" x-cloak action="{{ route('criteria.update', $c->id) }}" method="POST" class="space-y-2 mt-2">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="text-[10px] uppercase font-bold text-slate-400">Nama Kriteria:</label>
                            <input type="text" name="name" x-model="name" required class="w-full text-xs p-1.5 border rounded-lg">
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-slate-400">Tipe Atribut:</label>
                            <select name="attribute_type" x-model="type" class="w-full text-xs p-1.5 border rounded-lg">
                                <option value="benefit">Benefit (Makin tinggi makin baik)</option>
                                <option value="cost">Cost (Makin rendah makin baik)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-slate-400">Satuan / Skala:</label>
                            <input type="text" name="unit_or_scale" x-model="unit" required class="w-full text-xs p-1.5 border rounded-lg">
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-slate-400">Deskripsi:</label>
                            <textarea name="description" x-model="desc" rows="2" class="w-full text-xs p-1.5 border rounded-lg"></textarea>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            <button type="submit" class="px-3 py-1 bg-brand-600 text-white rounded text-xs font-bold">Simpan</button>
                            <button type="button" @click="editing = false" class="px-3 py-1 bg-slate-200 text-slate-700 rounded text-xs">Batal</button>
                        </div>
                    </form>
                </div>

                <div x-show="!editing" class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Parameter SPK</span>
                    <button type="button" @click="editing = true" class="text-brand-600 hover:text-brand-800 font-bold">
                        Edit Kriteria &rarr;
                    </button>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection

