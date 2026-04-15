@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="QuickSDR" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-sm">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="QuickSDR" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-sm">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:brand>
@endif
