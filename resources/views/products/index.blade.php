@extends('layouts.app')

@section('title', 'Master Data 30 Alternatif Produk - Camilan Jimerto')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Master Data 30 Alternatif Produk</h1>
            <p class="text-xs text-slate-500 mt-1">
                Katalog 30 produk camilan khas Madura UMKM Camilan Jimerto (A1 s.d. A30) yang dinilai dalam sistem pendukung keputusan.
            </p>
        </div>
        <div class="text-xs font-bold bg-brand-50 text-brand-700 px-3 py-1.5 rounded-xl border border-brand-200">
            Total Produk: 30 Alternatif Aktif
        </div>
    </div>

    <!-- Products Grid / Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3 w-16">Kode</th>
                        <th class="p-3">Nama Produk Alternatif</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Rekap Bulanan</th>
                        <th class="p-3 text-center">Transaksi Nota Tercatat</th>
                        <th class="p-3 text-center">Aksi Edit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($products as $p)
                        <tr class="hover:bg-slate-50 transition" x-data="{ editing: false, name: '{{ $p->name }}', active: {{ $p->is_active ? 1 : 0 }} }">
                            <td class="p-3 font-mono font-bold text-brand-700">{{ $p->code }}</td>
                            <td class="p-3 font-bold text-slate-900">
                                <span x-show="!editing">{{ $p->name }}</span>
                                <input x-show="editing" x-cloak type="text" x-model="name" class="px-2 py-1 text-xs border rounded-lg w-full">
                            </td>
                            <td class="p-3 text-center">
                                <template x-if="!editing">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $p->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $p->is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </template>
                                <select x-show="editing" x-cloak x-model="active" class="text-xs border rounded-lg p-1">
                                    <option value="1">Aktif</option>
                                    <option value="0">Non-Aktif</option>
                                </select>
                            </td>
                            <td class="p-3 text-center text-slate-600 font-mono">
                                {{ $p->monthly_sales_recaps_count }} bulan
                            </td>
                            <td class="p-3 text-center text-slate-600 font-mono">
                                {{ $p->transactions_count }} transaksi
                            </td>
                            <td class="p-3 text-center">
                                <div x-show="!editing">
                                    <button type="button" @click="editing = true" class="text-brand-600 hover:text-brand-800 font-bold">
                                        Edit
                                    </button>
                                </div>
                                <div x-show="editing" x-cloak class="flex items-center justify-center gap-1">
                                    <form action="{{ route('products.update', $p->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="name" :value="name">
                                        <input type="hidden" name="is_active" :value="active">
                                        <button type="submit" class="px-2 py-1 bg-brand-600 text-white rounded text-[10px] font-bold">Simpan</button>
                                    </form>
                                    <button type="button" @click="editing = false" class="px-2 py-1 bg-slate-200 text-slate-700 rounded text-[10px]">Batal</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

