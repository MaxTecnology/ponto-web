@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="auth-wrapper">
            @php
                $brandName = config('branding.display_name', config('app.name', 'Sistema de Ponto G2A'));
                $logoPath = config('branding.logo_path');
                $logoUrl = $logoPath && file_exists(public_path($logoPath)) ? asset($logoPath) : null;
            @endphp

            <section class="auth-hero">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="space-y-6">
                        <span class="auth-hero-badge">Acesso Seguro</span>
                        <div class="space-y-3">
                            <h2 class="text-3xl font-semibold leading-tight">{{ $brandName }}</h2>
                            <p class="text-sm text-white/80">Sistema integrado de ponto eletrônico com autenticação protegida.</p>
                        </div>
                    </div>

                    @if ($logoUrl)
                        <div class="relative z-10 mt-10 flex items-center justify-center">
                            <div class="rounded-[calc(var(--radius-md)-0.5rem)] border border-white/15 bg-white/10 p-6 backdrop-blur">
                                <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="max-h-40 w-auto object-contain opacity-95">
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="auth-card">
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold text-[rgb(var(--color-text))]">Bem-vindo de volta</h1>
                    <p class="text-sm text-[rgb(var(--color-muted))]">Entre com seu e-mail corporativo para acessar o sistema de ponto.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="auth-form">
                    @csrf
                    <div class="space-y-1">
                        <label for="email" class="app-label">E-mail</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="app-input" placeholder="seu.nome@g2a.com.br">
                        @error('email')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="password" class="app-label">Senha</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="app-input" placeholder="••••••••">
                        @error('password')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-remember">
                        <label class="inline-flex items-center gap-2 text-sm text-[rgb(var(--color-muted))]">
                            <input type="checkbox" name="remember" class="app-checkbox" {{ old('remember') ? 'checked' : '' }}>
                            <span>Lembrar-me neste dispositivo</span>
                        </label>
                    </div>

                    <button type="submit" class="app-button w-full justify-center">Entrar</button>
                </form>

                <p class="text-xs text-[rgb(var(--color-muted))]">Em caso de dúvidas ou dificuldades de acesso, contate o RH.</p>
            </section>
        </div>
    </div>
@endsection
