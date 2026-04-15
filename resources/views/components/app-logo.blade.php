@props([
    'sidebar' => false,
])

{{-- Sidebar / header brand — renders the landing-page QSDR word logo
     (blue→cyan gradient) as a single image. The SVG already contains
     the letterforms, so we don't render the flux:brand name prop. --}}
@if($sidebar)
    <a href="{{ $attributes->get('href') ?? url('/') }}" {{ $attributes->class(['flex items-center px-2 py-1'])->except('href') }} wire:navigate>
        <img src="{{ asset('brand/qsdr-logo.svg') }}" alt="QuickSDR" class="h-6 w-auto" />
    </a>
@else
    <a href="{{ $attributes->get('href') ?? url('/') }}" {{ $attributes->class(['flex items-center'])->except('href') }} wire:navigate>
        <img src="{{ asset('brand/qsdr-logo.svg') }}" alt="QuickSDR" class="h-7 w-auto" />
    </a>
@endif
