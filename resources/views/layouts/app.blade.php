<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SPK Penentuan Prioritas Produksi') - UMKM Camilan Jimerto</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (CDN with Plugins) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fdf8f6',
                            100: '#f2e8e5',
                            200: '#eaddd7',
                            300: '#e0cec7',
                            400: '#d2bab0',
                            500: '#9e472a', // Terracotta warm Jimerto tone
                            600: '#89361c',
                            700: '#732c16',
                            800: '#5e2513',
                            900: '#4e2012',
                        },
                        amber: {
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    @stack('styles')
</head>
<body class="h-full text-slate-800 antialiased font-sans flex flex-col" x-data="{ sidebarOpen: false }">

    <div class="min-h-full flex flex-col">
        <!-- Top Navigation Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Mobile hamburger & Brand -->
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-brand-600 flex items-center justify-center text-white font-bold text-xl shadow-md">
                                CJ
                            </div>
                            <div>
                                <span class="font-extrabold text-slate-900 tracking-tight text-lg block leading-tight">Camilan Jimerto</span>
                                <span class="text-xs font-semibold text-brand-600 tracking-wider uppercase block">SPK AHP + SAW Penentuan Prioritas Produksi</span>
                            </div>
                        </a>
                    </div>

                    <!-- Right Controls: Role Badge, Quick Switcher, User Menu -->
                    <div class="flex items-center gap-3">
                        <!-- Quick Role Switcher for Demo / Testing Convenience -->
                        <div class="hidden sm:flex items-center bg-slate-100 p-1 rounded-xl text-xs font-medium">
                            <span class="px-2 text-slate-500">Mode:</span>
                            <a href="{{ route('quick-login', 'admin') }}" class="px-2.5 py-1 rounded-lg transition {{ Auth::user()->role === 'admin' ? 'bg-white text-brand-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                                Admin
                            </a>
                            <a href="{{ route('quick-login', 'pemilik_umkm') }}" class="px-2.5 py-1 rounded-lg transition {{ Auth::user()->role === 'pemilik_umkm' ? 'bg-white text-amber-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                                Pemilik UMKM
                            </a>
                        </div>

                        <!-- User Profile Pill -->
                        <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-700 font-bold text-sm">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div class="hidden md:block text-left">
                                <div class="text-xs font-semibold text-slate-900 leading-none">{{ Auth::user()->name }}</div>
                                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-500 mt-0.5">
                                    {{ Auth::user()->role === 'admin' ? 'Administrator' : 'Pemilik UMKM' }}
                                </div>
                            </div>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" title="Keluar" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition ml-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 flex overflow-hidden">
            <!-- Sidebar Navigation -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 pt-20 md:pt-0 transform transition-transform duration-200 ease-in-out md:translate-x-0 md:static md:inset-auto shrink-0 flex flex-col justify-between">
                <div class="p-4 space-y-6 overflow-y-auto">
                    <!-- Navigation Links -->
                    <div class="space-y-1">
                        <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Menu Utama</div>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition {{ request()->routeIs('dashboard') ? 'bg-brand-50 text-brand-600 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Dashboard SPK
                        </a>
                        <a href="{{ route('sales-recaps.transactions') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition {{ request()->routeIs('sales-recaps.transactions') ? 'bg-brand-50 text-brand-600 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            Data Transaksi Nota
                            <span class="ml-auto text-[10px] bg-slate-200 text-slate-700 font-bold px-1.5 py-0.5 rounded-full">426</span>
                        </a>
                        <a href="{{ route('ahp.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition {{ request()->routeIs('ahp.*') ? 'bg-brand-50 text-brand-600 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Kuesioner Bobot AHP
                        </a>
                    </div>

                    <!-- Admin Operational Management -->
                    <div class="space-y-1">
                        <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                            Kelola Produksi (Admin)
                        </div>
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('sales-recaps.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition {{ request()->routeIs('sales-recaps.index') ? 'bg-brand-50 text-brand-600 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Rekap Penjualan Bulanan
                                <span class="ml-auto w-2 h-2 rounded-full bg-emerald-500"></span>
                            </a>
                            <a href="{{ route('assessments.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition {{ request()->routeIs('assessments.*') ? 'bg-brand-50 text-brand-600 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Penilaian Operasional (C2-C5)
                            </a>
                            <a href="{{ route('runs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition {{ request()->routeIs('runs.*') ? 'bg-brand-50 text-brand-600 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Periode Evaluasi
                            </a>
                            <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition {{ request()->routeIs('products.*') ? 'bg-brand-50 text-brand-600 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                30 Alternatif Produk
                            </a>
                            <a href="{{ route('criteria.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition {{ request()->routeIs('criteria.*') ? 'bg-brand-50 text-brand-600 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                5 Kriteria Penilaian
                            </a>
                        @else
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500">
                                Mode Pemilik UMKM aktif. Anda dapat melihat analisis prioritas & mengisi kuesioner perbandingan kriteria AHP.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer Sidebar Info -->
                <div class="p-4 border-t border-slate-200 bg-slate-50 text-[11px] text-slate-500 space-y-1">
                    <div class="font-bold text-slate-700">Camilan Jimerto Pamekasan</div>
                    <div>Metode: AHP (Bobot) + SAW (Peringkat)</div>
                    <div class="text-[10px] text-slate-400">© 2026 Jimerto SPK System</div>
                </div>
            </aside>

            <!-- Backdrop for Mobile Sidebar -->
            <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-xs md:hidden"></div>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-xs" x-data="{ show: true }" x-show="show">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="flex-1 text-sm font-medium">{{ session('success') }}</div>
                        <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">&times;</button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-start gap-3 shadow-xs" x-data="{ show: true }" x-show="show">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div class="flex-1 text-sm font-medium">{{ session('warning') }}</div>
                        <button @click="show = false" class="text-amber-500 hover:text-amber-700">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3 shadow-xs" x-data="{ show: true }" x-show="show">
                        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="flex-1 text-sm font-medium">{{ session('error') }}</div>
                        <button @click="show = false" class="text-rose-500 hover:text-rose-700">&times;</button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

