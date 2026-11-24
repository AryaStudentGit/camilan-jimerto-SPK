@extends('layouts.app')

@section('title', 'Dashboard SPK - Belum Ada Data')

@section('content')
<div class="max-w-md mx-auto text-center py-16 space-y-4">
    <div class="w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mx-auto text-2xl font-bold">
        !
    </div>
    <h2 class="text-lg font-bold text-slate-900">Belum Ada Periode Evaluasi Aktif</h2>
    <p class="text-xs text-slate-500">
        Silakan buat atau aktifkan periode evaluasi perhitungan SPK untuk menampilkan analisis ranking 30 produk.
    </p>
    <a href="{{ route('runs.index') }}" class="inline-flex px-4 py-2 rounded-xl bg-brand-600 text-white font-bold text-xs shadow-md">
        Kelola Periode Evaluasi &rarr;
    </a>
</div>
@endsection

