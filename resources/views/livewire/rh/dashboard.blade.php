<div class="space-y-6">
    @php
        $exportQuery = array_filter([
            'inicio' => $startDate,
            'fim' => $endDate,
            'busca' => $userSearch ?: null,
            'tipo' => $type,
            'sem_geo' => $flagSemGeo ? '1' : null,
            'ip_novo' => $flagIpNovo ? '1' : null,
            'fingerprint_novo' => $flagFingerprintNovo ? '1' : null,
        ], fn ($value) => !is_null($value) && $value !== '');
    @endphp

    <section class="app-card p-6 space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="app-section-heading">Dashboard de Batidas</h2>
                <p class="app-section-subtitle">Filtre por período, colaborador, tipo ou flags antifraude.</p>
            </div>
            <a href="{{ route('rh.export', $exportQuery) }}" class="app-button md:self-start">
                Exportar CSV
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="space-y-1">
                <label for="inicio" class="app-label">Data início</label>
                <input type="date" id="inicio" wire:model.live="startDate" class="app-input">
            </div>
            <div class="space-y-1">
                <label for="fim" class="app-label">Data fim</label>
                <input type="date" id="fim" wire:model.live="endDate" class="app-input">
            </div>
            <div class="space-y-1">
                <label for="busca" class="app-label">Colaborador (nome ou e-mail)</label>
                <input type="text" id="busca" placeholder="Buscar" wire:model.live.debounce.500ms="userSearch" class="app-input">
            </div>
            <div class="space-y-1">
                <label for="tipo" class="app-label">Tipo</label>
                <select id="tipo" wire:model.live="type" class="app-input">
                    <option value="">Todos</option>
                    @foreach ($tipoLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3 rounded-[calc(var(--radius-sm))] border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-surface))] px-4 py-3 shadow-sm md:col-span-1">
                <input type="checkbox" id="flag-sem-geo" wire:model.live="flagSemGeo" class="app-checkbox">
                <label for="flag-sem-geo" class="text-sm text-[rgb(var(--color-text))]">Sem geolocalização</label>
            </div>
            <div class="flex items-center gap-3 rounded-[calc(var(--radius-sm))] border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-surface))] px-4 py-3 shadow-sm md:col-span-1">
                <input type="checkbox" id="flag-ip" wire:model.live="flagIpNovo" class="app-checkbox">
                <label for="flag-ip" class="text-sm text-[rgb(var(--color-text))]">IP novo (30 dias)</label>
            </div>
            <div class="flex items-center gap-3 rounded-[calc(var(--radius-sm))] border border-[rgb(var(--color-border))]/60 bg-[rgb(var(--color-surface))] px-4 py-3 shadow-sm md:col-span-1">
                <input type="checkbox" id="flag-fingerprint" wire:model.live="flagFingerprintNovo" class="app-checkbox">
                <label for="flag-fingerprint" class="text-sm text-[rgb(var(--color-text))]">Fingerprint novo (30 dias)</label>
            </div>
        </div>
    </section>

    <section class="app-card p-0">
        <div class="flex flex-col gap-1 p-6 pb-0 sm:flex-row sm:items-baseline sm:justify-between">
            <div>
                <h3 class="app-section-heading text-base">Batidas registradas</h3>
                <p class="app-section-subtitle">Exibindo {{ $punches->count() }} de {{ $punches->total() }} resultados.</p>
            </div>
            <div class="text-xs text-[rgb(var(--color-muted))]">Última atualização: {{ now(config('app.timezone'))->format('d/m/Y H:i') }}</div>
        </div>

        <div class="app-table-wrapper">
            <table class="app-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Colaborador</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Data/Hora (local)</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">IP & Localização</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Dispositivo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Flags</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgb(var(--color-border))]/50">
                    @forelse ($punches as $punch)
                        @php
                            $tsLocal = \App\Support\Timezone::toLocal($punch->ts_server);
                            $device = $punch->device_info ?? [];
                            $geo = $punch->geo ?? null;
                            $typeBadgeMap = [
                                'IN' => 'app-badge-success',
                                'OUT' => 'app-badge-info',
                                'BREAK_IN' => 'app-badge-warning',
                                'BREAK_OUT' => 'app-badge-warning',
                            ];
                            $typeBadge = $typeBadgeMap[$punch->type] ?? 'app-badge-neutral';
                            $deviceSummary = trim(($device['os'] ?? $device['platform'] ?? '—') . ' · ' . ($device['browser'] ?? '—'));

                            $capabilities = [];
                            if (!empty($device['device_category'])) {
                                $capabilities[] = ucfirst($device['device_category']);
                            }
                            if (isset($device['hardware_concurrency'])) {
                                $capabilities[] = 'Cores: ' . $device['hardware_concurrency'];
                            }
                            if (isset($device['device_memory'])) {
                                $capabilities[] = 'RAM: ' . $device['device_memory'] . 'GB';
                            }

                            $screenDetails = [];
                            if (!empty($device['screen']['width']) && !empty($device['screen']['height'])) {
                                $screenDetails[] = $device['screen']['width'] . '×' . $device['screen']['height'];
                            }
                            if (!empty($device['screen']['color_depth'])) {
                                $screenDetails[] = $device['screen']['color_depth'] . ' bits';
                            }

                            $languages = isset($device['languages']) && is_array($device['languages'])
                                ? implode(', ', array_slice($device['languages'], 0, 3))
                                : null;

                            $brands = isset($device['brands']) && is_array($device['brands'])
                                ? implode(', ', array_slice($device['brands'], 0, 3))
                                : null;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <div class="font-semibold text-[rgb(var(--color-text))]">{{ $punch->user->name }}</div>
                                <div class="text-xs text-[rgb(var(--color-muted))]">{{ $punch->user->email }}</div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="app-badge {{ $typeBadge }}">{{ $tipoLabels[$punch->type] ?? $punch->type }}</span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-[rgb(var(--color-text))]">
                                <div class="font-medium">{{ $tsLocal?->format('d/m/Y H:i:s') ?? '—' }}</div>
                                <div class="text-xs text-[rgb(var(--color-muted))]">UTC {{ $punch->ts_server?->format('Y-m-d H:i:s') }}</div>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-[rgb(var(--color-text))]">
                                <div class="font-medium">{{ $punch->ip }}</div>
                                @if ($geo && isset($geo['lat'], $geo['lon']))
                                    <div class="text-xs text-[rgb(var(--color-muted))]">Lat {{ $geo['lat'] }} · Lon {{ $geo['lon'] }}</div>
                                    <a href="https://www.google.com/maps?q={{ $geo['lat'] }},{{ $geo['lon'] }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[rgb(var(--color-primary))] hover:underline">
                                        Ver no Maps
                                    </a>
                                @else
                                    <div class="text-xs text-[rgb(var(--color-muted))]">Sem geo</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-[rgb(var(--color-muted))] space-y-1">
                                <div class="font-semibold text-[rgb(var(--color-text))]">{{ $deviceSummary }}</div>
                                @if ($capabilities)
                                    <div>{{ implode(' · ', $capabilities) }}</div>
                                @endif
                                @if ($screenDetails)
                                    <div>Tela: {{ implode(' · ', $screenDetails) }}</div>
                                @endif
                                <div>TZ: {{ $device['timezone'] ?? '—' }}</div>
                                @if ($languages)
                                    <div>Idiomas: {{ $languages }}</div>
                                @endif
                                @if ($brands)
                                    <div>UA Brands: {{ $brands }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="app-tag-list">
                                    @if ($punch->sem_geo_flag)
                                        <span class="app-badge app-badge-warning uppercase">sem_geo</span>
                                    @endif
                                    @if ($punch->ip_novo_flag)
                                        <span class="app-badge app-badge-info uppercase">ip_novo</span>
                                    @endif
                                    @if ($punch->fingerprint_novo_flag)
                                        <span class="app-badge app-badge-danger uppercase">fingerprint_novo</span>
                                    @endif
                                    @if (! $punch->sem_geo_flag && ! $punch->ip_novo_flag && ! $punch->fingerprint_novo_flag)
                                        <span class="app-badge app-badge-neutral uppercase">ok</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center">
                                <div class="app-empty-state">
                                    <span> Nenhuma batida encontrada com os filtros atuais. Ajuste os parâmetros acima para ampliar a busca. </span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-[rgb(var(--color-border))]/60 px-6 py-4 text-xs text-[rgb(var(--color-muted))]">
            <div>Mostrando página {{ $punches->currentPage() }} de {{ $punches->lastPage() }}</div>
            {{ $punches->links() }}
        </div>
    </section>
</div>
