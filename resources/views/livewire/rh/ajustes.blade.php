<div class="space-y-6">
    <section class="app-card p-6">
        <h2 class="app-section-heading">Solicitações de Ajuste Pendentes</h2>
        <p class="app-section-subtitle">Revise os pedidos, defina um parecer e registre um comentário opcional para o colaborador.</p>
    </section>

    <section class="app-card p-6 space-y-4">
        @forelse ($pendentes as $ajuste)
            <article class="app-card-muted border border-[rgb(var(--color-border))]/60 p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-2">
                        <div>
                            <h3 class="text-base font-semibold text-[rgb(var(--color-text))]">{{ $ajuste->user->name }}</h3>
                            <p class="text-xs text-[rgb(var(--color-muted))]">{{ $ajuste->user->email }}</p>
                        </div>
                        <div class="text-sm text-[rgb(var(--color-text))]">
                            Data solicitada:
                            <span class="font-medium">{{ \Carbon\CarbonImmutable::parse($ajuste->date)->format('d/m/Y') }}</span>
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
                        <span class="app-badge app-badge-neutral uppercase">Pendente</span>
                        <div class="flex flex-wrap gap-2">
                            <button wire:click="selecionar({{ $ajuste->id }}, 'aprovar')" class="app-button-success">Aprovar</button>
                            <button wire:click="selecionar({{ $ajuste->id }}, 'rejeitar')" class="app-button-danger">Rejeitar</button>
                        </div>
                    </div>
                </div>

                @if ($selecionadoId === $ajuste->id)
                    <form wire:submit.prevent="processar" class="mt-4 space-y-4 border-t border-[rgb(var(--color-border))]/60 pt-4">
                        <div>
                            <label for="comentario-{{ $ajuste->id }}" class="app-label">Comentário (opcional)</label>
                            <textarea id="comentario-{{ $ajuste->id }}" wire:model="comentario" rows="3" class="app-input" placeholder="Compartilhe detalhes relevantes com o colaborador"></textarea>
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
    </section>
</div>
