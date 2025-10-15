<div class="space-y-6">
    @php
        $exportQuery = array_filter([
            'inicio' => $startDate,
            'fim' => $endDate,
            'busca' => $userSearch ?: null,
            'tipo' => $type,
            'ip_novo' => $flagIpNovo ? '1' : null,
            'fingerprint_novo' => $flagFingerprintNovo ? '1' : null,
        ], fn ($value) => !is_null($value) && $value !== '');

        $cards = [
            [
                'label' => 'Batidas filtradas',
                'value' => number_format($summary['total'] ?? 0),
                'description' => 'Total de registros após os filtros atuais.',
            ],
            [
                'label' => 'Colaboradores únicos',
                'value' => number_format($summary['unique_users'] ?? 0),
                'description' => 'Usuários distintos que aparecem nos resultados.',
            ],
            [
                'label' => '% com geolocalização',
                'value' => ($summary['geo_percent'] ?? 100) . '%',
                'description' => 'Batidas com geo válida dentro do período.',
            ],
            [
                'label' => 'Alertas ativos',
                'value' => number_format(($summary['ip_novo'] ?? 0) + ($summary['fingerprint_novo'] ?? 0)),
                'description' => 'Soma de IPs e dispositivos inéditos.',
            ],
        ];
    @endphp

    <section class="app-card p-6 space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="app-section-heading">Dashboard de Batidas</h2>
                <p class="app-section-subtitle">Acompanhe registros, flags e tendências das batidas em tempo real.</p>
            </div>
            @php
                $ipNovoClasses = $flagIpNovo ? 'border-[rgb(var(--color-primary))] bg-[rgb(var(--color-primary))]/10 text-[rgb(var(--color-primary))]' : '';
                $fingerprintClasses = $flagFingerprintNovo ? 'border-rose-500 bg-rose-50 text-rose-600' : '';
            @endphp
            <div class="flex gap-2">
                <button type="button" wire:click="toggleFlag('ip_novo')" class="app-button-ghost text-sm px-3 py-1.5 {{ $ipNovoClasses }}">
                    IP novo ({{ $summary['ip_novo'] ?? 0 }})
                </button>
                <button type="button" wire:click="toggleFlag('fingerprint_novo')" class="app-button-ghost text-sm px-3 py-1.5 {{ $fingerprintClasses }}">
                    Fingerprint novo ({{ $summary['fingerprint_novo'] ?? 0 }})
                </button>
                <a href="{{ route('rh.export', $exportQuery) }}" class="app-button md:self-start">
                    Exportar CSV
                </a>
            </div>
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
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <div class="app-card p-4">
                <p class="text-xs uppercase tracking-wide text-[rgb(var(--color-muted))]">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold text-[rgb(var(--color-text))]">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-[rgb(var(--color-muted))]">{{ $card['description'] }}</p>
            </div>
        @endforeach
    </section>

    @if (($summary['sem_geo'] ?? 0) > 0)
        <div class="app-alert-warning">
            <p class="font-semibold">{{ $summary['sem_geo'] }} batidas antigas sem geolocalização.</p>
            <p class="text-sm text-[rgb(var(--color-muted))]">Esses registros podem ser anteriores à regra de geo obrigatória; mantenha-os monitorados.</p>
        </div>
    @endif

    <section class="app-card p-0">
        <div class="flex flex-col gap-1 p-6 pb-0 sm:flex-row sm:items-baseline sm:justify-between">
            <div>
                <h3 class="app-section-heading text-base">Batidas registradas</h3>
                <p class="app-section-subtitle">Exibindo {{ $punches->count() }} de {{ $punches->total() }} resultados.</p>
            </div>
            <div class="text-xs text-[rgb(var(--color-muted))]">Atualizado em {{ now(config('app.timezone'))->format('d/m/Y H:i') }}</div>
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

                            $rowHighlight = '';
                            if ($punch->sem_geo_flag) {
                                $rowHighlight = 'bg-amber-50/70';
                            } elseif ($punch->ip_novo_flag) {
                                $rowHighlight = 'bg-sky-50/70';
                            } elseif ($punch->fingerprint_novo_flag) {
                                $rowHighlight = 'bg-rose-50/70';
                            }
                        @endphp
                        <tr class="{{ $rowHighlight }}">
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
                                <div class="font-medium">{{ $punch->ip ?? '—' }}</div>
                                @if ($geo && isset($geo['lat'], $geo['lon']))
                                    <div class="text-xs text-[rgb(var(--color-muted))]">Lat {{ $geo['lat'] }} · Lon {{ $geo['lon'] }}</div>
                                    <a href="https://www.google.com/maps?q={{ $geo['lat'] }},{{ $geo['lon'] }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[rgb(var(--color-primary))] hover:underline">
                                        Ver no Maps
                                    </a>
                                @else
                                    <div class="text-xs text-[rgb(var(--color-muted))]">Geo obrigatória ausente</div>
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
                                        <span class="app-badge app-badge-warning uppercase" title="Geolocalização ausente ou negada.">sem_geo</span>
                                    @endif
                                    @if ($punch->ip_novo_flag)
                                        <span class="app-badge app-badge-info uppercase" title="IP inédito para o colaborador nos últimos 30 dias.">ip_novo</span>
                                    @endif
                                    @if ($punch->fingerprint_novo_flag)
                                        <span class="app-badge app-badge-danger uppercase" title="Dispositivo/navegador novo em relação aos últimos 30 dias.">fingerprint_novo</span>
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
                                    <span>Nenhuma batida encontrada com os filtros atuais. Ajuste os parâmetros acima para ampliar a busca.</span>
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
