<div class="space-y-6">
    @php
        $exportQuery = [
            'inicio' => $startDate,
            'fim' => $endDate,
        ];

        $periodStart = $startDate ? \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $startDate) : null;
        $periodEnd = $endDate ? \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $endDate) : null;

        $missingSample = collect($missingExits)->take(5);
        $checklistLabels = [
            'ajustes' => [
                'title' => 'Ajustes resolvidos',
                'description' => 'Todos os pedidos de ajuste foram aprovados ou rejeitados.',
            ],
            'dias_sem_saida' => [
                'title' => 'Dias sem saída revisados',
                'description' => 'As faltas de saída foram justificadas e comunicadas.',
            ],
            'relatorios' => [
                'title' => 'Relatórios gerados',
                'description' => 'Arquivos exportados/arquivados e prontos para auditoria.',
            ],
        ];
        $checklistCompleto = ! in_array(false, $checklist, true);
    @endphp

    <section class="app-card p-6 space-y-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="app-section-heading">Fechamento de Período</h2>
                <p class="app-section-subtitle">Conclua o checklist, gere evidências e consolide o período com rastreabilidade.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('rh.export', $exportQuery) }}" class="app-button-secondary">Exportar CSV</a>
                <a href="{{ route('rh.ajustes') }}" class="app-button-ghost">Abrir ajustes</a>
                <button
                    wire:click="fecharPeriodo"
                    class="app-button-success"
                    @if (! $checklistCompleto) disabled @endif
                    wire:loading.attr="disabled"
                >
                    Fechar período
                </button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-1">
                <label for="inicio" class="app-label">Data início</label>
                <input type="date" id="inicio" wire:model.live="startDate" class="app-input">
            </div>
            <div class="space-y-1">
                <label for="fim" class="app-label">Data fim</label>
                <input type="date" id="fim" wire:model.live="endDate" class="app-input">
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($checklistLabels as $key => $item)
                @php
                    $checked = $checklist[$key] ?? false;
                    $buttonClasses = $checked
                        ? 'border-emerald-400 bg-emerald-50 text-emerald-900'
                        : 'border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-surface))] text-[rgb(var(--color-text))] hover:border-[rgb(var(--color-primary))]';
                @endphp
                <button
                    type="button"
                    wire:click="toggleChecklist('{{ $key }}')"
                    class="flex flex-col items-start gap-2 rounded-[var(--radius-md)] border px-4 py-3 text-left shadow-sm transition {{ $buttonClasses }}"
                >
                    <span class="text-sm font-semibold">{{ $item['title'] }}</span>
                    <span class="text-xs text-[rgb(var(--color-muted))]">{{ $item['description'] }}</span>
                    <span class="mt-2 inline-flex items-center gap-1 text-xs font-semibold">
                        @if($checked)
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Concluído
                        @else
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span> Em aberto
                        @endif
                    </span>
                </button>
            @endforeach
        </div>

        <div class="grid gap-4 md:grid-cols-[2fr_1fr]">
            <div class="space-y-1">
                <label for="observacao" class="app-label">Observações (opcional)</label>
                <textarea id="observacao" wire:model.defer="observacao" rows="3" class="app-input" placeholder="Ex.: Relatórios enviados ao financeiro em 02/10."></textarea>
            </div>
            <div class="space-y-1">
                <label for="confirmacao" class="app-label">Confirmação</label>
                <input id="confirmacao" wire:model.defer="confirmacao" class="app-input" placeholder="Digite CONFIRMAR">
                <p class="text-xs text-[rgb(var(--color-muted))]">Exigido para evitar fechamentos acidentais.</p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="app-card p-4">
            <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Ajustes pendentes</p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">{{ number_format($pendingAdjustments) }}</p>
            <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">Acesse RH › Ajustes para resolver antes do fechamento.</p>
        </div>
        <div class="app-card p-4">
            <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Dias sem saída</p>
            <p class="mt-2 text-2xl font-semibold text-rose-600">{{ count($missingExits) }}</p>
            <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">Verifique as marcações e confirme se há justificativa.</p>
        </div>
        <div class="app-card p-4">
            <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Período selecionado</p>
            <p class="mt-2 text-2xl font-semibold text-[rgb(var(--color-text))]">
                {{ $periodStart?->format('d/m') }} → {{ $periodEnd?->format('d/m') }}
            </p>
            <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">{{ $periodStart?->locale(app()->getLocale())->translatedFormat('F Y') }}</p>
        </div>
        <div class="app-card p-4">
            <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Último fechamento</p>
            @if (!empty($fechamentos))
                @php $ultimo = end($fechamentos); @endphp
                <p class="mt-2 text-2xl font-semibold text-[rgb(var(--color-text))]">{{ \Carbon\CarbonImmutable::parse($ultimo['end'])->format('d/m') }}</p>
                <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">Fechado em {{ \Carbon\CarbonImmutable::parse($ultimo['closed_at'])->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
            @else
                <p class="mt-2 text-2xl font-semibold text-[rgb(var(--color-text))]">—</p>
                <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">Nenhum fechamento registrado ainda.</p>
            @endif
        </div>
    </section>

    <section class="app-card p-6 space-y-4">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
            <div>
                <h3 class="app-section-heading text-base">Pendências detalhadas</h3>
                <p class="app-section-subtitle">Reveja os principais pontos antes de consolidar o período.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs text-[rgb(var(--color-muted))]">
                <a class="app-button-ghost" href="{{ route('rh.dashboard', ['inicio' => $startDate, 'fim' => $endDate]) }}">Abrir no dashboard</a>
                <a class="app-button-ghost" href="{{ route('rh.ajustes') }}">Resolver ajustes</a>
            </div>
        </div>

        <div class="space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Dias sem saída registrada ({{ count($missingExits) }})</h4>
            @forelse ($missingSample as $item)
                <div class="rounded-[var(--radius-md)] border border-rose-200/80 bg-rose-50/70 p-4 text-sm text-[rgb(var(--color-text))]">
                    <div class="font-semibold">{{ $item['user']->name }} <span class="text-xs text-[rgb(var(--color-muted))]">{{ $item['user']->email }}</span></div>
                    <div>Data: {{ \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $item['date'])->locale(app()->getLocale())->translatedFormat('d \d\e F, Y') }}</div>
                    <div>Último evento: {{ $item['last_type'] }} às {{ $item['last_time']->format('H:i') }}</div>
                </div>
            @empty
                <div class="app-empty-state">
                    <span>Nenhuma inconsistência encontrada no período atual.</span>
                </div>
            @endforelse

            @if (count($missingExits) > $missingSample->count())
                <div class="text-xs text-[rgb(var(--color-muted))]">
                    + {{ count($missingExits) - $missingSample->count() }} outros dias listados nas consultas do dashboard.
                </div>
            @endif
        </div>
    </section>

    <section class="app-card p-6 space-y-4">
        <h3 class="app-section-heading text-base">Histórico de fechamentos</h3>
        @if (!empty($fechamentos))
            <ol class="relative border-l border-[rgb(var(--color-border))]/60 pl-6">
                @foreach (array_reverse($fechamentos) as $registro)
                    <li class="mb-6 last:mb-0">
                        <span class="absolute -left-1.5 mt-1 h-3 w-3 rounded-full bg-[rgb(var(--color-primary))]"></span>
                        <div class="rounded-[var(--radius-md)] border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-surface-alt))] p-4 text-sm text-[rgb(var(--color-text))]">
                            <div class="font-semibold">{{ \Carbon\CarbonImmutable::parse($registro['start'])->format('d/m/Y') }} → {{ \Carbon\CarbonImmutable::parse($registro['end'])->format('d/m/Y') }}</div>
                            <div>Fechado em {{ \Carbon\CarbonImmutable::parse($registro['closed_at'])->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                            <div>Executor: {{ $registro['closed_by_name'] ?? ('Usuário #' . ($registro['closed_by'] ?? '?')) }}</div>
                            @if (! empty($registro['note']))
                                <p class="mt-2 text-xs text-[rgb(var(--color-muted))]">Observação: {{ $registro['note'] }}</p>
                            @endif
                            @if (! empty($registro['checklist']))
                                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                    @foreach ($checklistLabels as $key => $item)
                                        @php $done = $registro['checklist'][$key] ?? false; @endphp
                                        <span class="app-badge {{ $done ? 'app-badge-success' : 'app-badge-neutral' }} uppercase">{{ $item['title'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="app-empty-state">
                <span>Nenhum fechamento registrado ainda. Assim que você concluir um período ele aparecerá aqui.</span>
            </div>
        @endif
    </section>
</div>
