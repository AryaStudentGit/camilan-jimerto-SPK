@extends('layouts.app')

@section('title', 'Data Transaksi Nota Penjualan - Camilan Jimerto')

@section('content')
<div class="space-y-6">

    <!-- Header & Statistics -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Audit & Data Transaksi Nota</h1>
            <p class="text-xs text-slate-500 mt-1">
                Seluruh 426 baris transaksi dari buku nota fisik Jimerto (Pamekasan, Sumenep, Sampang, Bangkalan) pembentuk rekapitulasi C1.
            </p>
        </div>
        <div class="flex items-center gap-4 text-xs font-semibold">
            <div class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700">
                Total Baris: <strong class="text-slate-900 font-bold">{{ number_format($stats['total_rows'], 0, ',', '.') }}</strong>
            </div>
            <div class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700">
                Total Qty: <strong class="text-blue-900 font-bold">{{ number_format($stats['total_qty'], 0, ',', '.') }} unit</strong>
            </div>
            <div class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700">
                Omzet Bersih: <strong class="text-emerald-900 font-bold">Rp {{ number_format($stats['total_turnover'], 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('sales-recaps.transactions') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Cari Kata Kunci:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="No nota, nama toko, barang..."
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Filter Bulan:</label>
                <select name="month" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none">
                    <option value="">Semua Bulan</option>
                    <option value="8" {{ request('month') == '8' ? 'selected' : '' }}>08 - Agustus 2026</option>
                    <option value="9" {{ request('month') == '9' ? 'selected' : '' }}>09 - September 2026</option>
                    <option value="10" {{ request('month') == '10' ? 'selected' : '' }}>10 - Oktober 2026</option>
                    <option value="11" {{ request('month') == '11' ? 'selected' : '' }}>11 - November 2026</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Filter Produk:</label>
                <select name="product_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none">
                    <option value="">Semua Produk (A1 - A30)</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->code }} - {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white transition flex-1">
                    Terapkan Filter
                </button>
                <a href="{{ route('sales-recaps.transactions') }}" class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3">ID Baris</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">No. Nota</th>
                        <th class="p-3">Toko / Mitra</th>
                        <th class="p-3">Wilayah</th>
                        <th class="p-3">Nama Barang Asli</th>
                        <th class="p-3">Mapping Produk (A1-A30)</th>
                        <th class="p-3 text-right">Qty</th>
                        <th class="p-3 text-right">Harga Satuan</th>
                        <th class="p-3 text-right">Omzet Bersih</th>
                        <th class="p-3">Status / Audit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($transactions as $t)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-mono text-slate-500">{{ $t->row_id }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $t->transaction_date?->format('d/m/Y') }}</td>
                            <td class="p-3 font-bold text-slate-700 whitespace-nowrap">{{ $t->invoice_no }}</td>
                            <td class="p-3 font-semibold text-slate-900">{{ $t->standard_store_name ?? $t->original_store_name }}</td>
                            <td class="p-3 text-slate-500 whitespace-nowrap">{{ $t->district }}</td>
                            <td class="p-3 text-slate-800">{{ $t->original_item_name }}</td>
                            <td class="p-3 font-bold text-brand-700">
                                @if($t->product)
                                    <span class="inline-flex items-center gap-1">
                                        <span class="bg-brand-100 text-brand-800 text-[10px] px-1.5 py-0.5 rounded font-black">{{ $t->product->code }}</span>
                                        {{ $t->product->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">Varian Campur</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono font-bold {{ $t->qty_clean < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ $t->qty_clean }}
                            </td>
                            <td class="p-3 text-right font-mono text-slate-600">
                                Rp {{ number_format($t->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900">
                                Rp {{ number_format($t->clean_turnover, 0, ',', '.') }}
                            </td>
                            <td class="p-3">
                                @if($t->transaction_status === 'Retur')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 uppercase">Retur</span>
                                @elseif($t->transaction_status === 'Batal_Kosong')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 uppercase">Dibatalkan</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">Penjualan</span>
                                @endif
                                @if($t->spelling_notes)
                                    <span class="block text-[10px] text-slate-400 mt-0.5 truncate max-w-xs" title="{{ $t->spelling_notes }}">{{ $t->spelling_notes }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-6 text-center text-slate-400">Tidak ada data transaksi yang cocok dengan kriteria pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination Links -->
        <div class="p-4 border-t border-slate-200">
            {{ $transactions->links() }}
        </div>
    </div>

</div>
@endsection

