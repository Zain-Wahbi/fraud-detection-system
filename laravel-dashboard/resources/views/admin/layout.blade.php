<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fraud Bank')</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #lang-dropdown { transition: opacity 0.15s, transform 0.15s; }
        #lang-dropdown.hidden { opacity:0; transform:translateY(-4px); pointer-events:none; }
        #lang-dropdown.open   { opacity:1; transform:translateY(0);   pointer-events:auto; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">

<div class="flex min-h-screen">
    <aside id="sidebar" class="w-64 bg-gray-900 flex flex-col fixed h-full z-10"
           style="right:0; border-left:1px solid #1f2937;">

        <!-- Logo -->
        <div class="p-6 border-b border-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">B</div>
                <div>
                    <div class="font-bold text-white">Fraud Bank</div>
                    <div class="text-xs text-gray-400" data-ar="نظام كشف الاحتيال" data-en="Fraud Detection System">نظام كشف الاحتيال</div>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 p-4 space-y-1">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }} transition">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span data-ar="لوحة التحكم" data-en="Dashboard">لوحة التحكم</span>
            </a>
            <a href="{{ route('transactions.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('transactions*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }} transition">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span data-ar="المعاملات" data-en="Transactions">المعاملات</span>
            </a>
            <a href="{{ route('blocklist') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('blocklist') ? 'bg-red-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }} transition">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                <span data-ar="قائمة الحظر" data-en="Blocklist">قائمة الحظر</span>
            </a>
            <a href="{{ route('simulation.form') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('simulation*') ? 'bg-purple-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }} transition">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span data-ar="محاكاة حيّة" data-en="Live Simulation">محاكاة حيّة</span>
            </a>
            <a href="{{ route('upload.form') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('upload*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }} transition">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span data-ar="رفع معاملات" data-en="Upload">رفع معاملات</span>
            </a>
        </nav>

        <!-- Language Selector -->
        <div class="p-4 border-t border-gray-800 space-y-3">
            <div class="relative">
                <button onclick="toggleDropdown()"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg bg-gray-800 hover:bg-gray-750 border border-gray-700 hover:border-gray-600 transition text-sm text-gray-300">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span id="lang-current-label">العربية</span>
                    </div>
                    <svg id="lang-chevron" class="w-4 h-4 text-gray-500 transition-transform duration-200 shrink-0"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div id="lang-dropdown"
                     class="hidden absolute bottom-full mb-2 w-full bg-gray-800 border border-gray-700 rounded-lg shadow-xl overflow-hidden z-50">
                    <button onclick="setLang('ar')"
                            class="w-full flex items-center gap-3 px-3 py-2.5 text-sm hover:bg-gray-700 transition text-start">
                            <span class="text-sm font-bold text-gray-300">AR</span>                        <div class="flex flex-col items-start">
                            <span class="text-gray-200 font-medium">العربية</span>
                            <span class="text-gray-500 text-xs">Arabic</span>
                        </div>
                        <svg id="check-ar" class="w-4 h-4 text-blue-400 ms-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    <div class="border-t border-gray-700"></div>
                    <button onclick="setLang('en')"
                            class="w-full flex items-center gap-3 px-3 py-2.5 text-sm hover:bg-gray-700 transition text-start">
                        <span class="text-sm font-bold text-gray-300">EN</span>
                        <div class="flex flex-col items-start">
                            <span class="text-gray-200 font-medium">English</span>
                            <span class="text-gray-500 text-xs">الإنجليزية</span>
                        </div>
                        <svg id="check-en" class="w-4 h-4 text-blue-400 ms-auto hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="text-xs text-gray-600 text-center">Admin Panel v1.0</div>
        </div>
    </aside>

    <main class="flex-1 p-8" id="main-content" style="margin-right:16rem;">
        @if(session('success'))
            <div class="mb-6 bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if(session('success_ar') || session('success_en'))
            <div class="mb-6 bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg"
                 data-ar="{{ session('success_ar') }}"
                 data-en="{{ session('success_en') }}">
                {{ session('success_ar') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>
</div>

<script>
let currentLang = localStorage.getItem('lang') || 'ar';
let dropdownOpen = false;

function toggleDropdown() {
    dropdownOpen = !dropdownOpen;
    const dd      = document.getElementById('lang-dropdown');
    const chevron = document.getElementById('lang-chevron');
    if (dropdownOpen) {
        dd.classList.remove('hidden');
        requestAnimationFrame(() => dd.classList.add('open'));
        chevron.style.transform = 'rotate(180deg)';
    } else {
        dd.classList.remove('open');
        dd.classList.add('hidden');
        chevron.style.transform = '';
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#lang-dropdown') && !e.target.closest('[onclick="toggleDropdown()"]')) {
        if (dropdownOpen) {
            const dd = document.getElementById('lang-dropdown');
            dd.classList.remove('open');
            dd.classList.add('hidden');
            document.getElementById('lang-chevron').style.transform = '';
            dropdownOpen = false;
        }
    }
});

function setLang(lang) {
    currentLang = lang;
    localStorage.setItem('lang', lang);
    applyLang(lang);
    const dd = document.getElementById('lang-dropdown');
    dd.classList.remove('open');
    dd.classList.add('hidden');
    document.getElementById('lang-chevron').style.transform = '';
    dropdownOpen = false;
}

function applyLang(lang) {
    const html    = document.getElementById('html-root');
    const main    = document.getElementById('main-content');
    const sidebar = document.getElementById('sidebar');
    const checkAr = document.getElementById('check-ar');
    const checkEn = document.getElementById('check-en');
    const label   = document.getElementById('lang-current-label');

    if (lang === 'en') {
        html.setAttribute('lang', 'en');
        html.setAttribute('dir', 'ltr');
        sidebar.style.right      = 'auto';
        sidebar.style.left       = '0';
        sidebar.style.borderLeft  = 'none';
        sidebar.style.borderRight = '1px solid #1f2937';
        main.style.marginRight   = '0';
        main.style.marginLeft    = '16rem';
        label.textContent = 'English';
        checkAr.classList.add('hidden');
        checkEn.classList.remove('hidden');
    } else {
        html.setAttribute('lang', 'ar');
        html.setAttribute('dir', 'rtl');
        sidebar.style.right      = '0';
        sidebar.style.left       = 'auto';
        sidebar.style.borderLeft  = '1px solid #1f2937';
        sidebar.style.borderRight = 'none';
        main.style.marginRight   = '16rem';
        main.style.marginLeft    = '0';
        label.textContent = 'العربية';
        checkAr.classList.remove('hidden');
        checkEn.classList.add('hidden');
    }

    // ✅ الإصلاح الرئيسي — بدون شرط children
    document.querySelectorAll('[data-ar][data-en]').forEach(el => {
        el.textContent = lang === 'en' ? el.dataset.en : el.dataset.ar;
    });

    // Placeholders
    document.querySelectorAll('[data-placeholder-ar][data-placeholder-en]').forEach(el => {
        el.setAttribute('placeholder', lang === 'en' ? el.dataset.placeholderEn : el.dataset.placeholderAr);
    });
}

document.addEventListener('DOMContentLoaded', () => applyLang(currentLang));
</script>
</body>
</html>