<div class="space-y-6">
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="app-card p-4">
            <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Pendentes</p>
            <p class="mt-2 text-2xl font-semibold text-[rgb(var(--color-text))]">{{ number_format($metrics['pendentes'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">Solicitações aguardando decisão.</p>
        </div>
        <div class="app-card p-4">
            <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Aprovados</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ number_format($metrics['aprovados'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">Total aprovado desde o início do mês.</p>
        </div>
        <div class="app-card p-4">
            <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Rejeitados</p>
            <p class="mt-2 text-2xl font-semibold text-rose-600">{{ number_format($metrics['rejeitados'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">Pedidos recusados após análise.</p>
        </div>
        <div class="app-card p-4">
            <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">Tempo médio de resposta</p>
            <p class="mt-2 text-2xl font-semibold text-[rgb(var(--color-text))]">
                {{ $metrics['tempo_medio'] ? $metrics['tempo_medio'] . ' min' : '—' }}
            </p>
            <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">Baseado nas decisões recentes.</p>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="app-card p-6 space-y-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                <div>
                    <h2 class="app-section-heading text-base">Pendências para tratar</h2>
                    <p class="app-section-subtitle">Selecione um ajuste para aprovar ou rejeitar com comentário.</p>
                </div>
                <span class="app-badge app-badge-neutral uppercase">{{ $metrics['pendentes'] ?? 0 }} em análise</span>
            </div>

            @forelse ($pendentes as $ajuste)
                @php
                    $isSelected = $selecionadoId === $ajuste->id;
                @endphp
                <article class="rounded-[var(--radius-md)] border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-surface-alt))] p-5 shadow-sm transition hover:border-[rgb(var(--color-primary))] @if($isSelected) border-[rgb(var(--color-primary))] bg-[rgb(var(--color-primary))]/5 @endif">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div>
                                <h3 class="text-base font-semibold text-[rgb(var(--color-text))]">{{ $ajuste->user->name }}</h3>
                                <p class="text-xs text-[rgb(var(--color-muted))]">{{ $ajuste->user->email }}</p>
                            </div>
                            <div class="text-sm text-[rgb(var(--color-text))]">
                                Data solicitada: <span class="font-medium">{{ \Carbon\CarbonImmutable::parse($ajuste->date)->format('d/m/Y') }}</span>
                            </div>
                            <p class="text-sm text-[rgb(var(--color-text))]">Motivo: <span class="font-medium">{{ $ajuste->reason }}</span></p>
                            <div class="text-xs text-[rgb(var(--color-muted))] space-y-1">
                                @if ($ajuste->from_ts)
                                    <div>Início sugerido: {{ $ajuste->from_ts->clone()->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                                @endif
                                @if ($ajuste->to_ts)
                                    <div>Fim sugerido: {{ $ajuste->to_ts->clone()->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                                @endif
                                <div>Solicitado em: {{ $ajuste->created_at->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 md:items-end">
                            <span class="app-badge app-badge-warning uppercase">Pendente</span>
                            <div class="flex flex-wrap gap-2">
                                <button wire:click="selecionar({{ $ajuste->id }}, 'aprovar')" class="app-button-success">Aprovar</button>
                                <button wire:click="selecionar({{ $ajuste->id }}, 'rejeitar')" class="app-button-danger">Rejeitar</button>
                            </div>
                        </div>
                    </div>

                    @if ($isSelected)
                        <form wire:submit.prevent="processar" class="mt-4 space-y-4 rounded-[calc(var(--radius-md)-2px)] border border-[rgb(var(--color-border))]/60 bg-white/70 p-4">
                            <div>
                                <label for="comentario-{{ $ajuste->id }}" class="app-label">Comentário (opcional)</label>
                                <textarea id="comentario-{{ $ajuste->id }}" wire:model="comentario" rows="3" class="app-input" placeholder="Compartilhe detalhes relevantes com o colaborador"></textarea>
                                <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">O colaborador verá esse comentário no espelho de ponto.</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" wire:click="cancelar" class="app-button-ghost">Cancelar</button>
                                <button type="submit" class="app-button">
                                    {{ $acao === 'aprovar' ? 'Confirmar aprovação' : 'Confirmar rejeição' }}
                                </button>
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <div class="app-empty-state">
                    <span>Nenhum ajuste pendente no momento. Quando um colaborador solicitar uma correção, ela aparecerá aqui.</span>
                </div>
            @endforelse
        </div>

        <aside class="app-card p-6 space-y-4">
            @if ($selecionado)
                <div class="flex items-center justify-between">
                    <h3 class="app-section-heading text-base">Detalhes selecionados</h3>
                    <span class="app-badge app-badge-neutral uppercase">{{ $acao === 'aprovar' ? 'Aprovação' : 'Rejeição' }}</span>
                </div>
                <div class="text-sm text-[rgb(var(--color-text))] space-y-2">
                    <div><span class="font-semibold">Colaborador:</span> {{ $selecionado->user->name }}</div>
                    <div>
                        <span class="font-semibold">Período:</span>
                        {{ $selecionado->from_ts?->clone()->setTimezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }} →
                        {{ $selecionado->to_ts?->clone()->setTimezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                    </div>
                    <div><span class="font-semibold">Motivo:</span> {{ $selecionado->reason }}</div>
                    <div><span class="font-semibold">Solicitado em:</span> {{ $selecionado->created_at->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                </div>

                @php
                    $audit = collect($selecionado->audit ?? [])->sortBy('at');
                @endphp
                @if ($audit->isNotEmpty())
                    <div class="space-y-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Histórico</h4>
                        <ul class="space-y-1 text-xs text-[rgb(var(--color-muted))]">
                            @foreach ($audit as $entry)
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 h-1.5 w-1.5 rounded-full bg-[rgb(var(--color-primary))]"></span>
                                    <span>
                                        <strong>{{ $entry['action'] ?? 'EVENTO' }}</strong>
                                        @if (isset($entry['comment']))
                                            — {{ $entry['comment'] }}
                                        @endif
                                        <br>
                                        <small>{{ isset($entry['at']) ? \Carbon\CarbonImmutable::parse($entry['at'])->setTimezone(config('app.timezone'))->format('d/m/Y H:i') : '' }}</small>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @else
                <div class="app-empty-state">
                    <span>Selecione um ajuste à esquerda para visualizar os detalhes e registrar seu parecer.</span>
                </div>
            @endif

            <div class="rounded-[var(--radius-md)] border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-surface-alt))] p-4 text-xs text-[rgb(var(--color-muted))]">
                <h4 class="text-sm font-semibold text-[rgb(var(--color-text))]">Boas práticas</h4>
                <ul class="mt-2 list-disc space-y-1 pl-4">
                    <li>Documente o motivo da aprovação ou rejeição para rastreabilidade.</li>
                    <li>Verifique batidas relacionadas no dashboard antes da decisão.</li>
                    <li>Use o histórico para comparar alterações anteriores.</li>
                </ul>
            </div>
        </aside>
    </section>

    <section class="app-card p-6 space-y-4">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
            <div>
                <h3 class="app-section-heading text-base">Histórico recente</h3>
                <p class="app-section-subtitle">Últimas decisões aplicadas pelo time de RH.</p>
            </div>
        </div>

        @if ($historico->isNotEmpty())
            <div class="app-table-wrapper">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Colaborador</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Comentário</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Decidido em</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgb(var(--color-border))]/50">
                        @foreach ($historico as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--color-text))]">
                                    <div class="font-semibold">{{ $item->user->name }}</div>
                                    <div class="text-xs text-[rgb(var(--color-muted))]">{{ $item->user->email }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @php
                                        $statusBadge = [
                                            'APROVADO' => 'app-badge-success',
                                            'REJEITADO' => 'app-badge-danger',
                                            'PENDENTE' => 'app-badge-warning',
                                        ][$item->status] ?? 'app-badge-neutral';
                                    @endphp
                                    <span class="app-badge {{ $statusBadge }} uppercase">{{ $item->status }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--color-muted))]">
                                    {{ collect($item->audit ?? [])->last()['comment'] ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-[rgb(var(--color-muted))]">
                                    {{ optional($item->decided_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                    @if ($item->approver)
                                        <div class="text-xs">por {{ $item->approver->name }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="app-empty-state">
                <span>Nenhuma decisão registrada ainda. Assim que um ajuste for aprovado ou rejeitado ele aparecerá aqui.</span>
            </div>
        @endif
    </section>
</div>
