<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => $survey->title])
    <meta name="robots" content="noindex,nofollow">
    @if ($embed ?? false)
        <style>
            body { background: transparent !important; }
            .survey-root { background: transparent !important; }
        </style>
    @endif
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <livewire:survey-form :survey="$survey" :response="$response" :embed="$embed ?? false" />

    @livewireScripts
    @fluxScripts
</body>
</html>
