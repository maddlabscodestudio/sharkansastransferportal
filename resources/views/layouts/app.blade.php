<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sharkansas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-black">

    <!-- NAVBAR -->
    <header class="w-full bg-[#0B1F2D] border-b border-[#1A3445] sticky top-0 z-50">
        <div class="w-full px-4 md:px-8 py-6 flex items-center justify-between">
            <img src="{{ asset('img/sharkcbb-logo.png') }}" alt="Logo" class="h-11">

            <div class="mb-6 flex justify-center">
                <div class="w-full max-w-3xl bg-white rounded-2xl border border-sky-300 bg-sky-100 px-6 py-4 text-sm text-slate-800 shadow-md">
                    <div class="text-center">
                        <div class="font-semibold uppercase tracking-wide text-[11px] text-sky-700 mb-2">Beta Notice</div>
                        <div>
                            SharkCBB Transfer Portal Tracker is currently in beta. If you spot any bugs, incorrect data, or missing portal entries, please email me at
                            <a href="mailto:maddlabsllc@gmail.com" class="font-semibold underline text-sky-700 hover:text-sky-900">maddlabsllc@gmail.com</a>.
                        </div>
                    </div>
                </div>
            </div>

            @php $path = request()->path(); @endphp

            <nav class="flex items-center gap-4 md:gap-6 text-sm font-medium">
                <a href="/" class="nav-link {{ $path === '/' ? 'active' : '' }}">Portal Stats</a>
                <a href="/portal" class="nav-link {{ str_contains($path, 'portal') && !str_contains($path, 'stats') ? 'active' : '' }}">Portal Feed</a>
                <!-- <a href="/about" class="nav-link {{ str_contains($path, 'about') ? 'active' : '' }}">About</a> -->
            </nav>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="w-full px-4 md:px-6 xl:px-8 py-6">
        @yield('content')
    </main>

</body>
</html>