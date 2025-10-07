<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        @isset($title)
            {{ $title }} ·
        @endisset
        {{ config('branding.display_name', config('app.name', 'Sistema de Ponto G2A')) }}
    </title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
    <div class="min-h-screen flex flex-col">
        <header class="sticky top-0 z-40 backdrop-blur bg-white/85 border-b border-[rgb(var(--color-border))]/60">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                @php
                    $brandName = config('branding.display_name', config('app.name', 'Sistema de Ponto G2A'));
                    $brandTagline = config('branding.tagline');
                    $logoPath = config('branding.logo_path');
                    $logoUrl = $logoPath && file_exists(public_path($logoPath)) ? asset($logoPath) : null;
                @endphp
                <a href="{{ auth()->check() ? route('ponto.index') : route('login') }}" class="app-brand">
                    @if ($logoUrl)
                        <span class="app-brand-logo">
                            <img src="{{ $logoUrl }}" alt="{{ $brandName }}">
                        </span>
                    @else
                        <span class="app-brand-badge">SP</span>
                    @endif
                    <span class="app-brand-text">
                        {{ $brandName }}
                        @if ($brandTagline)
                            <span class="app-brand-subtitle">{{ $brandTagline }}</span>
                        @endif
                    </span>
                </a>

                <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-6">
                    <nav class="app-nav">
                        @auth
                            @cannot('view-rh')
                                <a href="{{ route('ponto.index') }}" class="app-nav-link @if(request()->routeIs('ponto.index')) is-active @endif">Bater Ponto</a>
                                <a href="{{ route('ponto.espelho') }}" class="app-nav-link @if(request()->routeIs('ponto.espelho')) is-active @endif">Meu Espelho</a>
                            @endcannot

                            @can('view-rh')
                                <div class="relative" data-rh-menu>
                                    <button type="button" data-rh-trigger class="app-nav-link @if(request()->routeIs('rh.*')) is-active @endif">
                                        RH
                                        <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.586l3.71-3.354a.75.75 0 011.02 1.096l-4.25 3.84a.75.75 0 01-1.02 0l-4.25-3.84a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <div data-rh-panel class="hidden absolute right-0 mt-2 w-48 rounded-md border border-[rgb(var(--color-border))]/70 bg-white shadow-lg ring-1 ring-black/5">
                                        <a href="{{ route('rh.dashboard') }}" class="block px-4 py-2 text-sm text-[rgb(var(--color-text))] hover:bg-[rgb(var(--color-surface-alt))]">Dashboard</a>
                                        <a href="{{ route('rh.ajustes') }}" class="block px-4 py-2 text-sm text-[rgb(var(--color-text))] hover:bg-[rgb(var(--color-surface-alt))]">Ajustes</a>
                                        <a href="{{ route('rh.fechamento') }}" class="block px-4 py-2 text-sm text-[rgb(var(--color-text))] hover:bg-[rgb(var(--color-surface-alt))]">Fechamento</a>
                                        <a href="{{ route('rh.export', []) }}" class="block px-4 py-2 text-sm text-[rgb(var(--color-text))] hover:bg-[rgb(var(--color-surface-alt))]">Exportar CSV</a>
                                    </div>
                                </div>
                            @endcan

                            @can('manage-roles')
                                <a href="{{ route('admin.users') }}" class="app-nav-link @if(request()->routeIs('admin.users')) is-active @endif">Admin</a>
                            @endcan

                            <a href="{{ route('perfil') }}" class="app-nav-link @if(request()->routeIs('perfil')) is-active @endif">Perfil</a>
                        @else
                            <a href="{{ route('login') }}" class="app-button">Entrar</a>
                        @endauth
                    </nav>

                    @auth
                        @php
                            $user = auth()->user();
                            $initial = strtoupper(mb_substr($user->name ?? 'U', 0, 1, 'UTF-8'));
                        @endphp
                        <div class="app-user-chip">
                            <span class="app-user-avatar">{{ $initial }}</span>
                            <div>
                                <div class="app-user-name">{{ $user->name }}</div>
                                <div class="app-user-role">{{ ucwords(str_replace('_', ' ', $user->role)) }}</div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                            @csrf
                            <button type="submit" class="app-button-secondary">Sair</button>
                        </form>
                    @endauth
                </div>
            </div>
        </header>
        <main class="flex-1">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
                @if (session('status'))
                    <div class="mb-6 app-alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 app-alert-danger">
                        <ul class="list-disc pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </div>
        </main>
        <footer class="py-6 text-center text-xs text-[rgb(var(--color-muted))]">
            {{ config('branding.display_name', config('app.name', 'Sistema de Ponto G2A')) }} © {{ now()->year }}
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menu = document.querySelector('[data-rh-menu]');
            if (!menu) return;

            const trigger = menu.querySelector('[data-rh-trigger]');
            const panel = menu.querySelector('[data-rh-panel]');
            if (!trigger || !panel) return;

            const close = () => panel.classList.add('hidden');
            const open = () => panel.classList.toggle('hidden');

            trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                open();
            });

            document.addEventListener('click', (event) => {
                if (!menu.contains(event.target)) {
                    close();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    close();
                }
            });
        });
    </script>
</body>
</html>
