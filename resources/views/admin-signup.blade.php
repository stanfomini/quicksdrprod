<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => __('Set up your admin account')])
</head>
<body class="min-h-screen bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 text-zinc-100 antialiased">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900/60 p-8 shadow-2xl backdrop-blur">
            <div class="mb-2 inline-flex h-10 w-10 items-center justify-center rounded-md bg-gradient-to-br from-indigo-500 to-fuchsia-500 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                    <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.272.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
            </div>

            <h1 class="mt-4 text-2xl font-semibold text-white">{{ __('Welcome — set up your admin account') }}</h1>

            @if ($emailHint)
                <p class="mt-2 text-sm text-zinc-400">
                    {{ __('This signup is intended for') }}
                    <span class="font-medium text-zinc-200">{{ $emailHint }}</span>.
                </p>
            @else
                <p class="mt-2 text-sm text-zinc-400">{{ __('You\'re a few seconds away from your dialer.') }}</p>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-md border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-300">
                    <ul class="list-disc ps-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin-signup.store', $token->token) }}" class="mt-6 flex flex-col gap-4">
                @csrf

                <flux:input
                    name="name"
                    :label="__('Your name')"
                    :value="old('name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Jane Smith"
                />

                <flux:input
                    name="email"
                    :label="__('Email address')"
                    :value="old('email', $emailHint)"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />

                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Choose a strong password')"
                    viewable
                />

                <flux:input
                    name="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Type it again')"
                />

                <flux:button variant="primary" type="submit" class="w-full" icon-trailing="arrow-right">
                    {{ __('Create admin account') }}
                </flux:button>
            </form>

            <p class="mt-6 text-center text-xs text-zinc-500">
                {{ __('This link can only be used once.') }}
            </p>
        </div>
    </main>

    @fluxScripts
</body>
</html>
