<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SPK Prioritas Produksi Camilan Jimerto</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        brand: { 500: '#9e472a', 600: '#89361c', 700: '#732c16' }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex items-center justify-center p-4">

    <div class="max-w-md w-full space-y-6">
        <!-- Logo & Branding -->
        <div class="text-center space-y-2">
            <div class="inline-flex w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-500 to-brand-500 items-center justify-center text-white font-extrabold text-2xl shadow-xl shadow-amber-500/20">
                CJ
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Camilan Jimerto</h1>
            <p class="text-xs text-slate-400 font-medium max-w-xs mx-auto">
                Sistem Pendukung Keputusan Penentuan Prioritas Produksi Menggunakan Metode Gabungan AHP & SAW
            </p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-2xl p-6 sm:p-8 shadow-2xl border border-white/20">
            @if(session('info'))
                <div class="mb-4 p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs">
                    {{ session('info') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                    {{ session('warning') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Email Pengguna</label>
                    <input type="email" name="email" value="{{ old('email', 'admin@jimerto.test') }}" required autofocus
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition outline-none">
                    @error('email')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Kata Sandi</label>
                    <input type="password" name="password" value="password" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition outline-none">
                    @error('password')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded text-brand-600 focus:ring-brand-500 mr-2">
                        Ingat sesi saya
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-brand-600 to-amber-600 hover:from-brand-700 hover:to-amber-700 text-white font-bold text-sm shadow-md transition transform active:scale-[0.99]">
                    Masuk ke Sistem
                </button>
            </form>

            <!-- Quick Demo Login Switcher -->
            <div class="mt-6 pt-6 border-t border-slate-200">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center mb-3">
                    Akses Cepat Pengujian (1-Klik)
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <a href="{{ route('quick-login', 'admin') }}"
                        class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 hover:border-brand-500 hover:bg-brand-50/50 transition group text-center">
                        <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-700 font-bold flex items-center justify-center text-xs mb-1.5 group-hover:scale-105 transition">
                            ADM
                        </div>
                        <span class="text-xs font-bold text-slate-800">Admin Produksi</span>
                        <span class="text-[10px] text-slate-500">Kelola Data & Rekap</span>
                    </a>
                    <a href="{{ route('quick-login', 'pemilik_umkm') }}"
                        class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 hover:border-amber-500 hover:bg-amber-50/50 transition group text-center">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 font-bold flex items-center justify-center text-xs mb-1.5 group-hover:scale-105 transition">
                            OWN
                        </div>
                        <span class="text-xs font-bold text-slate-800">Pemilik UMKM</span>
                        <span class="text-[10px] text-slate-500">Kuesioner AHP & SPK</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center text-xs text-slate-400">
            UMKM Camilan Jimerto • Studi Kasus SPK Pamekasan, Madura
        </div>
    </div>

</body>
</html>

