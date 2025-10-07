<div class="space-y-6">
    <section class="app-card flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <h2 class="text-lg font-semibold text-[rgb(var(--color-text))]">Espelho de Ponto</h2>
            <p class="text-sm text-[rgb(var(--color-muted))]">Selecione o mês para visualizar suas batidas convertidas para horário local.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
                <label for="mes" class="app-label">Mês</label>
                <select id="mes" wire:model.live="month" class="app-input w-48">
                    @foreach ($availableMonths as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="button" class="app-button" data-scroll-to="solicitar-ajuste">Solicitar ajuste</button>
                <button type="button" class="app-button-secondary" data-scroll-to="minhas-solicitacoes">Minhas solicitações</button>
            </div>
        </div>
    </section>

    <section class="app-card p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-md font-semibold text-[rgb(var(--color-text))]">Resumo do mês</h3>
                <p class="text-sm text-[rgb(var(--color-muted))]">Total bruto registrado: <span class="font-medium text-[rgb(var(--color-text))]">{{ $totalFormatado }} horas</span>.</p>
            </div>
        </div>

        @php
            $totalsByType = $monthStats['totals_by_type'] ?? [];
        @endphp

        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($totalsByType as $type => $count)
                <div class="rounded-lg border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-surface-alt))] px-3 py-2">
                    <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">{{ $tipoLabels[$type] ?? $type }}</p>
                    <p class="text-xl font-semibold text-[rgb(var(--color-text))]">{{ $count }}</p>
                </div>
            @endforeach
        </div>

        @php
            $semGeoSummary = $alertSummaries['sem_geo'] ?? ['total' => 0, 'sample' => [], 'remaining' => 0];
            $ipAlertSummary = $alertSummaries['ip_novo'] ?? ['total' => 0, 'sample' => [], 'remaining' => 0];
            $fingerprintSummary = $alertSummaries['fingerprint_novo'] ?? ['total' => 0, 'sample' => [], 'remaining' => 0];
        @endphp

        @if (($semGeoSummary['total'] ?? 0) > 0)
            <div class="mt-4 app-alert-warning">
                <p class="font-semibold">
                    {{ $semGeoSummary['total'] }}
                    {{ \Illuminate\Support\Str::plural('dia', $semGeoSummary['total']) }}
                    com batidas sem geolocalização.
                </p>
                <p class="text-sm text-[rgb(var(--color-muted))]">
                    Verifique se a permissão de localização está ativa. Dias:
                    {{ implode(', ', $semGeoSummary['sample'] ?? []) }}
                    @if (($semGeoSummary['remaining'] ?? 0) > 0)
                        e {{ $semGeoSummary['remaining'] }} outros
                    @endif
                    .
                </p>
            </div>
        @endif

        @if (($ipAlertSummary['total'] ?? 0) > 0 || ($fingerprintSummary['total'] ?? 0) > 0)
            <div class="mt-3 app-alert-info">
                <p class="font-semibold">Alterações detectadas.</p>
                @if (($ipAlertSummary['total'] ?? 0) > 0)
                    <p class="text-sm text-[rgb(var(--color-muted))]">
                        Novos endereços IP em {{ $ipAlertSummary['total'] }}
                        {{ \Illuminate\Support\Str::plural('dia', $ipAlertSummary['total']) }}
                        (ex.: {{ implode(', ', $ipAlertSummary['sample'] ?? []) }}
                        @if (($ipAlertSummary['remaining'] ?? 0) > 0)
                            e {{ $ipAlertSummary['remaining'] }} outros
                        @endif
                        ).
                    </p>
                @endif
                @if (($fingerprintSummary['total'] ?? 0) > 0)
                    <p class="text-sm text-[rgb(var(--color-muted))]">
                        Novos dispositivos/navegadores em {{ $fingerprintSummary['total'] }}
                        {{ \Illuminate\Support\Str::plural('dia', $fingerprintSummary['total']) }}
                        (ex.: {{ implode(', ', $fingerprintSummary['sample'] ?? []) }}
                        @if (($fingerprintSummary['remaining'] ?? 0) > 0)
                            e {{ $fingerprintSummary['remaining'] }} outros
                        @endif
                        ).
                    </p>
                @endif
                <p class="text-xs text-[rgb(var(--color-muted))]">Confirme se as mudanças são esperadas e, em caso de dúvidas, contate o RH.</p>
            </div>
        @endif

        <div class="mt-6 space-y-4">
            @forelse ($report as $day)
                <div class="app-card-muted border border-[rgb(var(--color-border))]/50 p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <button type="button" wire:click="selecionarDia('{{ $day['date'] }}')" class="text-left text-base font-semibold text-[rgb(var(--color-primary))] hover:underline">
                                {{ \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $day['date'])->locale(app()->getLocale())->translatedFormat('d \d\e F, l') }}
                            </button>
                            <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">{{ $this->formatDuration($day['worked_seconds']) }} horas trabalhadas</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($day['flag_summary']['sem_geo'] ?? false)
                                <span class="app-badge app-badge-warning text-xs uppercase" title="Existe batida sem localização neste dia. Oriente o colaborador a habilitar o acesso à localização.">Alerta: sem GEO</span>
                            @endif
                            @if ($day['flag_summary']['ip_novo'] ?? false)
                                <span class="app-badge app-badge-neutral text-xs uppercase" title="Foi detectado IP diferente dos últimos 30 dias. Verifique se é um acesso legítimo.">IP novo</span>
                            @endif
                            @if ($day['flag_summary']['fingerprint_novo'] ?? false)
                                <span class="app-badge app-badge-neutral text-xs uppercase" title="Foi detectado dispositivo/navegador diferente dos últimos 30 dias.">Dispositivo novo</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="app-table">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-[rgb(var(--color-muted))]">Tipo</th>
                                    <th class="px-3 py-2 text-left font-medium text-[rgb(var(--color-muted))]">Horário (local)</th>
                                    <th class="px-3 py-2 text-left font-medium text-[rgb(var(--color-muted))]">Observação</th>
                                    <th class="px-3 py-2 text-left font-medium text-[rgb(var(--color-muted))]">Alertas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[rgb(var(--color-border))]/60">
                                @foreach ($day['punches'] as $punch)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-[rgb(var(--color-text))]">{{ $tipoLabels[$punch['type']] ?? $punch['type'] }}</td>
                                        <td class="px-3 py-2 text-[rgb(var(--color-muted))]">{{ $punch['ts_local']->format('H:i') }}</td>
                                        <td class="px-3 py-2 text-[rgb(var(--color-muted))]">{{ $punch['observacao'] ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            <div class="flex flex-wrap gap-1">
                                                @php
                                                    $flags = $punch['flags'] ?? [];
                                                @endphp
                                                @if (!empty($flags['sem_geo']))
                                                    <span class="app-badge app-badge-warning text-xs uppercase" title="Localização ausente. Oriente o colaborador a permitir o uso de GPS/localização.">Sem GEO</span>
                                                @endif
                                                @if (!empty($flags['ip_novo']))
                                                    <span class="app-badge app-badge-neutral text-xs uppercase" title="IP não visto nos últimos 30 dias para este colaborador.">IP novo</span>
                                                @endif
                                                @if (!empty($flags['fingerprint_novo']))
                                                    <span class="app-badge app-badge-neutral text-xs uppercase" title="Dispositivo/navegador diferente dos últimos 30 dias.">Dispositivo novo</span>
                                                @endif
                                                @if (empty($flags['sem_geo']) && empty($flags['ip_novo']) && empty($flags['fingerprint_novo']))
                                                    <span class="text-xs text-[rgb(var(--color-muted))]">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="text-sm text-[rgb(var(--color-muted))]">Nenhuma batida registrada neste mês.</p>
            @endforelse
        </div>
    </section>

    <section id="solicitar-ajuste" class="app-card p-6 space-y-4">
        <div>
            <h3 class="app-section-heading text-base">Solicitar Ajuste</h3>
            <p class="app-section-subtitle">Peça ao RH para corrigir horários ausentes ou equivocadas deste mês.</p>
        </div>
        <form wire:submit.prevent="solicitarAjuste" class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1">
                <label for="ajuste-data" class="app-label">Data</label>
                <input type="date" id="ajuste-data" wire:model="ajusteData" class="app-input" required>
                @error('ajusteData')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-1">
                <label for="ajuste-inicio" class="app-label">Horário início (opcional)</label>
                <input type="time" id="ajuste-inicio" wire:model="ajusteInicio" class="app-input">
                @error('ajusteInicio')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-1">
                <label for="ajuste-fim" class="app-label">Horário fim (opcional)</label>
                <input type="time" id="ajuste-fim" wire:model="ajusteFim" class="app-input">
                @error('ajusteFim')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2 space-y-1">
                <label for="ajuste-motivo" class="app-label">Motivo</label>
                <textarea id="ajuste-motivo" wire:model="ajusteMotivo" rows="4" class="app-input" placeholder="Descreva o que precisa ser ajustado" required></textarea>
                @error('ajusteMotivo')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2 flex justify-end gap-2">
                @if ($editingRequestId)
                    <button type="button" wire:click="cancelarEdicao" class="app-button-ghost">Cancelar edição</button>
                @endif
                <button type="submit" class="app-button">
                    {{ $editingRequestId ? 'Atualizar ajuste' : 'Enviar ajuste' }}
                </button>
            </div>
        </form>
    </section>

    <section id="minhas-solicitacoes" class="app-card p-6 space-y-4">
        <div>
            <h3 class="app-section-heading text-base">Minhas Solicitações Neste Mês</h3>
            <p class="app-section-subtitle">Acompanhe o status e comentários do RH para cada ajuste solicitado.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <label for="filtro-status" class="app-label">Filtrar por status</label>
                <select id="filtro-status" wire:model.live="ajusteStatusFiltro" class="app-input w-48">
                    <option value="todos">Todos</option>
                    <option value="pendente">Pendentes</option>
                    <option value="aprovado">Aprovados</option>
                    <option value="rejeitado">Rejeitados</option>
                </select>
            </div>
        </div>
        @php
            $solicitacoesFiltradas = $this->solicitacoesFiltradas;
        @endphp
        @if ($solicitacoesFiltradas->isNotEmpty())
            <div class="app-table-wrapper">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Período</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Motivo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Atualizado em</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgb(var(--color-border))]/50">
                        @foreach ($solicitacoesFiltradas as $request)
                            @php
                                $statusBadge = [
                                    'PENDENTE' => 'app-badge-warning',
                                    'APROVADO' => 'app-badge-success',
                                    'REJEITADO' => 'app-badge-danger',
                                ][$request->status] ?? 'app-badge-neutral';
                                $latestComment = collect($request->audit ?? [])->filter(fn ($entry) => isset($entry['comment']))->last();
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--color-text))]">{{ \Carbon\CarbonImmutable::parse($request->date)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--color-muted))]">
                                    @if ($request->from_ts || $request->to_ts)
                                        {{ optional($request->from_ts)->setTimezone(config('app.timezone'))->format('H:i') ?? '—' }} →
                                        {{ optional($request->to_ts)->setTimezone(config('app.timezone'))->format('H:i') ?? '—' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--color-text))]">{{ $request->reason }}</td>
                                <td class="px-4 py-3">
                                    <span class="app-badge {{ $statusBadge }} uppercase">{{ $request->status }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--color-muted))]">
                                    {{ optional($request->updated_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                    @if ($latestComment)
                                        <div class="mt-1 text-xs text-[rgb(var(--color-muted))]">
                                            Comentário RH: {{ $latestComment['comment'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($request->status === 'PENDENTE')
                                        <div class="flex justify-end gap-2">
                                            <button type="button" wire:click="iniciarEdicao({{ $request->id }})" class="app-button-ghost text-xs">Editar</button>
                                            <button type="button" wire:click="removerSolicitacao({{ $request->id }})" class="app-button-danger text-xs">Remover</button>
                                        </div>
                                    @else
                                        <span class="text-xs text-[rgb(var(--color-muted))]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="app-empty-state">
                <span>Nenhuma solicitação encontrada para o filtro selecionado.</span>
            </div>
        @endif
    </section>
</div>

@push('scripts')
    <script>
        (() => {
            const initScrollButtons = () => {
                document.querySelectorAll('[data-scroll-to]').forEach((button) => {
                    if (button.dataset.bound === 'true') {
                        return;
                    }

                    button.dataset.bound = 'true';
                    button.addEventListener('click', (event) => {
                        event.preventDefault();

                        const targetId = button.dataset.scrollTo;
                        const target = document.getElementById(targetId);

                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });
            };

            if (document.readyState !== 'loading') {
                initScrollButtons();
            } else {
                document.addEventListener('DOMContentLoaded', initScrollButtons, { once: true });
            }

            document.addEventListener('livewire:navigated', initScrollButtons);
        })();
    </script>
@endpush
