@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="app-card p-6">
        <h1 class="text-2xl font-semibold">Design Tokens</h1>
        <p class="mt-2 text-sm text-[rgb(var(--color-muted))]">Referência rápida das cores e componentes usados nas telas.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-5">
            <div class="rounded-lg border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-primary))] p-4 text-white">
                <div class="text-xs uppercase tracking-wide opacity-80">Primária</div>
                <div class="mt-2 font-semibold">#4F46E5</div>
            </div>
            <div class="rounded-lg border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-primary-dark))] p-4 text-white">
                <div class="text-xs uppercase tracking-wide opacity-80">Primária (hover)</div>
                <div class="mt-2 font-semibold">#4338CA</div>
            </div>
            <div class="rounded-lg border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-success))] p-4 text-white">
                <div class="text-xs uppercase tracking-wide opacity-80">Sucesso</div>
                <div class="mt-2 font-semibold">#10B981</div>
            </div>
            <div class="rounded-lg border border-[rgb(var(--color-border))]/60 bg-white p-4">
                <div class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Superfície</div>
                <div class="mt-2 font-semibold text-[rgb(var(--color-text))]">#FFFFFF</div>
            </div>
            <div class="rounded-lg border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-surface-alt))] p-4">
                <div class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Superfície secundária</div>
                <div class="mt-2 font-semibold text-[rgb(var(--color-text))]">#F1F5F9</div>
            </div>
        </div>
    </section>

    <section class="app-card p-6">
        <h2 class="text-xl font-semibold">Botões</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            <button class="app-button">Primário</button>
            <button class="app-button-secondary">Secundário</button>
            <button class="app-button" disabled>Desabilitado</button>
            <button class="app-button-success">Sucesso</button>
            <button class="app-button-danger">Perigo</button>
            <button class="app-button-ghost">Fantasma</button>
        </div>
    </section>

    <section class="app-card p-6">
        <h2 class="text-xl font-semibold">Alertas</h2>
        <div class="mt-4 space-y-3">
            <div class="app-alert-success">Exemplo de sucesso</div>
            <div class="app-alert-danger">Exemplo de erro</div>
        </div>
    </section>

    <section class="app-card p-6">
        <h2 class="text-xl font-semibold">Badges</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            <span class="app-badge">Padrão</span>
            <span class="app-badge app-badge-success">Sucesso</span>
            <span class="app-badge app-badge-warning">Atenção</span>
            <span class="app-badge app-badge-danger">Crítico</span>
            <span class="app-badge app-badge-info">Informativo</span>
            <span class="app-badge app-badge-neutral">Neutro</span>
        </div>
    </section>

    <section class="app-card p-6">
        <h2 class="text-xl font-semibold">Componentes de apoio</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="app-stat">
                <div class="app-stat-title">Indicador</div>
                <div class="app-stat-value">128</div>
                <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">Use em dashboards ou blocos de destaque.</p>
            </div>
            <div class="app-empty-state">
                <span>Estado vazio padrão para tabelas e listagens.</span>
            </div>
        </div>
    </section>

    <section class="app-card p-6">
        <h2 class="text-xl font-semibold">Inputs</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="app-label" for="sg-text">Texto</label>
                <input id="sg-text" class="app-input" placeholder="Digite…">
            </div>
            <div>
                <label class="app-label" for="sg-select">Seleção</label>
                <select id="sg-select" class="app-input">
                    <option>Opção A</option>
                    <option>Opção B</option>
                </select>
            </div>
        </div>
    </section>
</div>
@endsection
