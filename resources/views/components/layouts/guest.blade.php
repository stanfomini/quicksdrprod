<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => $title ?? config('app.name', 'QuickSDR')])
</head>
<body class="min-h-screen bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 text-zinc-100 antialiased">
    <main class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 text-lg font-semibold tracking-tight text-white">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-gradient-to-br from-indigo-500 to-fuchsia-500 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                    <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.272.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
            </span>
            QuickSDR
        </a>

        <div class="w-full max-w-2xl rounded-2xl border border-zinc-800 bg-zinc-900/60 shadow-2xl backdrop-blur">
            {{ $slot }}
        </div>
    </main>

    @fluxScripts
</body>
</html>
