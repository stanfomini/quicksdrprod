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
    @if (! empty($isPreview))
        <div class="bg-amber-100 dark:bg-amber-900 border-b border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-100 px-4 py-2 text-sm text-center">
            Preview mode — this survey is <strong>{{ $survey->status?->label() ?? 'not published' }}</strong>. Publish it to make it visible to the public.
        </div>
    @endif

    <livewire:survey-form :survey="$survey" :response="$response" :embed="$embed ?? false" />

    @livewireScripts
    @fluxScripts
</body>
</html>
