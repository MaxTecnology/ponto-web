<div class="space-y-6">
    @php
        $exportQuery = [
            'inicio' => $startDate,
            'fim' => $endDate,
        ];
    @endphp

    <section class="app-card p-6 space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="app-section-heading">Fechamento de Período</h2>
                <p class="app-section-subtitle">Revise pendências antes de consolidar e exportar o período selecionado.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('rh.export', $exportQuery) }}" class="app-button-secondary">Exportar CSV</a>
                <button wire:click="fecharPeriodo" class="app-button-success">Fechar período</button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-1">
                <label for="inicio" class="app-label">Data início</label>
                <input type="date" id="inicio" wire:model="startDate" class="app-input">
            </div>
            <div class="space-y-1">
                <label for="fim" class="app-label">Data fim</label>
                <input type="date" id="fim" wire:model="endDate" class="app-input">
            </div>
        </div>
    </section>

    <section class="app-card p-6 space-y-6">
        <div>
            <h3 class="app-section-heading text-base">Pendências</h3>
            <p class="app-section-subtitle">Monitore ajustes em aberto e inconsistências antes de fechar o ciclo.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="app-stat border-amber-200/80 bg-amber-50/60">
                <div class="app-stat-title text-amber-700">Ajustes pendentes</div>
                <div class="app-stat-value text-amber-900">{{ $pendingAdjustments }}</div>
                <p class="mt-1 text-xs text-amber-700">Analise os ajustes em RH › Ajustes para liberar o fechamento.</p>
            </div>
            <div class="app-stat border-rose-200/80 bg-rose-50/60">
                <div class="app-stat-title text-rose-700">Dias sem saída registrada</div>
                <div class="app-stat-value text-rose-900">{{ count($missingExits) }}</div>
                <p class="mt-1 text-xs text-rose-700">Revise as marcações do dia para evitar inconsistências.</p>
            </div>
        </div>

        <div class="space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Detalhes de dias sem saída</h4>
            @forelse ($missingExits as $item)
                <div class="app-card-muted border border-[rgb(var(--color-border))]/60 p-4 text-sm text-[rgb(var(--color-text))]">
                    <div class="font-semibold text-[rgb(var(--color-text))]">{{ $item['user']->name }} <span class="text-xs text-[rgb(var(--color-muted))]">{{ $item['user']->email }}</span></div>
                    <div>Data: {{ \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $item['date'])->locale(app()->getLocale())->translatedFormat('d \d\e F, Y') }}</div>
                    <div>Último evento: {{ $item['last_type'] }} às {{ $item['last_time']->format('H:i') }}</div>
                </div>
            @empty
                <div class="app-empty-state">
                    <span>Nenhuma inconsistência encontrada no período atual.</span>
                </div>
            @endforelse
        </div>
    </section>

    <section class="app-card p-6 space-y-4">
        <h3 class="app-section-heading text-base">Histórico de Fechamentos</h3>
        @forelse (array_reverse($fechamentos) as $registro)
            <div class="app-card-muted border border-[rgb(var(--color-border))]/60 p-4 text-sm text-[rgb(var(--color-text))]">
                <div class="font-semibold">Período: {{ \Carbon\CarbonImmutable::parse($registro['start'])->format('d/m/Y') }} - {{ \Carbon\CarbonImmutable::parse($registro['end'])->format('d/m/Y') }}</div>
                <div>Fechado em: {{ \Carbon\CarbonImmutable::parse($registro['closed_at'])->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                <div>Executor: {{ $registro['closed_by_name'] ?? ('Usuário #' . ($registro['closed_by'] ?? '?')) }}</div>
            </div>
        @empty
            <div class="app-empty-state">
                <span>Nenhum fechamento registrado ainda. Assim que você concluir um período ele aparecerá aqui.</span>
            </div>
        @endforelse
    </section>
</div>
