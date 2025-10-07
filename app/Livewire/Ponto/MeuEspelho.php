<?php

namespace App\Livewire\Ponto;

use App\Models\AdjustRequest;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class MeuEspelho extends Component
{
    public string $month;

    public array $report = [];

    public int $totalSeconds = 0;

    public array $monthStats = [
        'totals_by_type' => [],
        'sem_geo_days' => 0,
        'ip_alert_days' => 0,
        'fingerprint_alert_days' => 0,
    ];

    public array $alertDays = [
        'sem_geo' => [],
        'ip_novo' => [],
        'fingerprint_novo' => [],
    ];

    public array $alertSummaries = [
        'sem_geo' => ['total' => 0, 'sample' => [], 'remaining' => 0],
        'ip_novo' => ['total' => 0, 'sample' => [], 'remaining' => 0],
        'fingerprint_novo' => ['total' => 0, 'sample' => [], 'remaining' => 0],
    ];

    public string $ajusteStatusFiltro = 'todos';

    /**
     * @var array<string, string>
     */
    public array $tipoLabels = [
        'IN' => 'Entrada',
        'OUT' => 'Saída',
        'BREAK_IN' => 'Início Pausa',
        'BREAK_OUT' => 'Fim Pausa',
    ];

    #[Validate('required|date')]
    public ?string $ajusteData = null;

    #[Validate('nullable|date_format:H:i')]
    public ?string $ajusteInicio = null;

    #[Validate('nullable|date_format:H:i')]
    public ?string $ajusteFim = null;

    #[Validate('required|string|min:5|max:1000')]
    public string $ajusteMotivo = '';

    /** @var \Illuminate\Support\Collection<int, AdjustRequest>|null */
    public $recentRequests;

    public ?int $editingRequestId = null;

    public function mount(): void
    {
        $this->month = CarbonImmutable::now(config('app.timezone'))->format('Y-m');
        $this->ajusteData = CarbonImmutable::now(config('app.timezone'))->format('Y-m-d');

        $this->loadReport();
    }

    public function render()
    {
        return view('livewire.ponto.meu-espelho', [
            'availableMonths' => $this->availableMonths(),
            'totalFormatado' => $this->formatDuration($this->totalSeconds),
            'tipoLabels' => $this->tipoLabels,
        ]);
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }

    public function updatedMonth(): void
    {
        $this->loadReport();
    }

    public function selecionarDia(string $date): void
    {
        $this->ajusteData = $date;
    }

    public function solicitarAjuste(): void
    {
        $this->validate();

        $user = $this->user();
        $tz = config('app.timezone');

        $from = $this->ajusteInicio ? CarbonImmutable::createFromFormat('Y-m-d H:i', $this->ajusteData . ' ' . $this->ajusteInicio, $tz)->utc() : null;
        $to = $this->ajusteFim ? CarbonImmutable::createFromFormat('Y-m-d H:i', $this->ajusteData . ' ' . $this->ajusteFim, $tz)->utc() : null;

        if ($from && $to && $from->gt($to)) {
            $this->addError('ajusteFim', 'Horário fim deve ser maior que o início.');
            return;
        }

        if ($this->editingRequestId) {
            $request = AdjustRequest::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->where('status', AdjustRequest::STATUS_PENDENTE)
                ->findOrFail($this->editingRequestId);

            $audit = $request->audit ?? [];
            $audit[] = [
                'action' => 'EDITADO',
                'by' => $user->getAuthIdentifier(),
                'at' => CarbonImmutable::now('UTC')->toIso8601String(),
            ];

            $request->update([
                'date' => $this->ajusteData,
                'from_ts' => $from,
                'to_ts' => $to,
                'reason' => $this->ajusteMotivo,
                'audit' => $audit,
            ]);

            $message = 'Solicitação de ajuste atualizada.';
            $this->notify('success', $message);
        } else {
            AdjustRequest::create([
                'user_id' => $user->getAuthIdentifier(),
                'date' => $this->ajusteData,
                'from_ts' => $from,
                'to_ts' => $to,
                'reason' => $this->ajusteMotivo,
            ]);

            $message = 'Solicitação de ajuste enviada para aprovação.';
            $this->notify('success', $message);
        }

        $this->reset(['ajusteInicio', 'ajusteFim', 'ajusteMotivo', 'editingRequestId']);
        $this->ajusteData = CarbonImmutable::now($tz)->format('Y-m-d');

        $this->loadSolicitacoesRecentes();
    }

    private function loadReport(): void
    {
        $tz = config('app.timezone');
        $monthRef = CarbonImmutable::createFromFormat('Y-m', $this->month, $tz)->startOfMonth();
        $startUtc = $monthRef->clone()->startOfMonth()->timezone('UTC');
        $endUtc = $monthRef->clone()->endOfMonth()->endOfDay()->timezone('UTC');
        $historyStart = $monthRef->clone()->subDays(30)->timezone('UTC');

        $user = $this->user();
        $punches = $user->punches()
            ->whereBetween('ts_server', [$historyStart, $endUtc])
            ->orderBy('ts_server')
            ->get();

        $ipLastSeen = [];
        $fingerprintLastSeen = [];
        $days = [];
        $totalSeconds = 0;
        $totalsByType = [
            'IN' => 0,
            'OUT' => 0,
            'BREAK_IN' => 0,
            'BREAK_OUT' => 0,
        ];
        $semGeoDays = [];
        $ipAlertDays = [];
        $fingerprintAlertDays = [];

        foreach ($punches as $punch) {
            if (! $punch->ts_server) {
                continue;
            }

            $tsServer = CarbonImmutable::instance($punch->ts_server)->setTimezone('UTC');
            $thirtyDaysAgo = $tsServer->subDays(30);

            $ipNovo = false;
            if ($punch->ip) {
                $lastSeen = $ipLastSeen[$punch->ip] ?? null;
                $ipNovo = ! $lastSeen || $lastSeen->lt($thirtyDaysAgo);
                $ipLastSeen[$punch->ip] = $tsServer;
            }

            $fingerprintNovo = false;
            if ($punch->fingerprint_hash) {
                $lastFingerprint = $fingerprintLastSeen[$punch->fingerprint_hash] ?? null;
                $fingerprintNovo = ! $lastFingerprint || $lastFingerprint->lt($thirtyDaysAgo);
                $fingerprintLastSeen[$punch->fingerprint_hash] = $tsServer;
            }

            if ($tsServer->lt($startUtc) || $tsServer->gt($endUtc)) {
                continue;
            }

            $tsLocal = Timezone::toLocal($punch->ts_server, $tz);
            $dateLocal = $tsLocal->format('Y-m-d');

            $days[$dateLocal]['date'] = $dateLocal;
            $days[$dateLocal]['punches'][] = [
                'id' => $punch->id,
                'type' => $punch->type,
                'ts_local' => $tsLocal,
                'observacao' => $punch->observacao,
                'flags' => [
                    'sem_geo' => empty($punch->geo) || ! $punch->geo_consent,
                    'ip_novo' => $ipNovo,
                    'fingerprint_novo' => $fingerprintNovo,
                ],
            ];

            if (array_key_exists($punch->type, $totalsByType)) {
                $totalsByType[$punch->type]++;
            } else {
                $totalsByType[$punch->type] = 1;
            }
        }

        ksort($days);

        foreach ($days as &$day) {
            $day['worked_seconds'] = $this->calculateWorkedSeconds($day['punches']);

            $flagSummary = [
                'sem_geo' => false,
                'ip_novo' => false,
                'fingerprint_novo' => false,
            ];

            foreach ($day['punches'] as &$punch) {
                $flagSummary['sem_geo'] = $flagSummary['sem_geo'] || ($punch['flags']['sem_geo'] ?? false);
                $flagSummary['ip_novo'] = $flagSummary['ip_novo'] || ($punch['flags']['ip_novo'] ?? false);
                $flagSummary['fingerprint_novo'] = $flagSummary['fingerprint_novo'] || ($punch['flags']['fingerprint_novo'] ?? false);
            }

            $day['flag_summary'] = $flagSummary;

            if ($flagSummary['sem_geo']) {
                $semGeoDays[] = $day['date'];
            }

            if ($flagSummary['ip_novo']) {
                $ipAlertDays[] = $day['date'];
            }

            if ($flagSummary['fingerprint_novo']) {
                $fingerprintAlertDays[] = $day['date'];
            }

            $totalSeconds += $day['worked_seconds'];
        }

        $this->report = $days;
        $this->totalSeconds = $totalSeconds;
        $this->alertDays = [
            'sem_geo' => array_values(array_unique($semGeoDays)),
            'ip_novo' => array_values(array_unique($ipAlertDays)),
            'fingerprint_novo' => array_values(array_unique($fingerprintAlertDays)),
        ];
        $this->monthStats = [
            'totals_by_type' => $totalsByType,
            'sem_geo_days' => count($this->alertDays['sem_geo']),
            'ip_alert_days' => count($this->alertDays['ip_novo']),
            'fingerprint_alert_days' => count($this->alertDays['fingerprint_novo']),
        ];
        $this->alertSummaries = [
            'sem_geo' => $this->summarizeAlertDays($this->alertDays['sem_geo']),
            'ip_novo' => $this->summarizeAlertDays($this->alertDays['ip_novo']),
            'fingerprint_novo' => $this->summarizeAlertDays($this->alertDays['fingerprint_novo']),
        ];

        $this->loadSolicitacoesRecentes();
    }

    private function loadSolicitacoesRecentes(): void
    {
        $user = $this->user();

        $monthRef = CarbonImmutable::createFromFormat('Y-m', $this->month, config('app.timezone'));

        $this->recentRequests = AdjustRequest::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereBetween('date', [
                $monthRef->startOfMonth()->format('Y-m-d'),
                $monthRef->endOfMonth()->format('Y-m-d'),
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    public function iniciarEdicao(int $requestId): void
    {
        $user = $this->user();
        $tz = config('app.timezone');

        $request = AdjustRequest::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('status', AdjustRequest::STATUS_PENDENTE)
            ->findOrFail($requestId);

        $this->editingRequestId = $request->id;
        $this->ajusteData = CarbonImmutable::parse($request->date)->format('Y-m-d');
        $this->ajusteInicio = $request->from_ts ? CarbonImmutable::parse($request->from_ts)->setTimezone($tz)->format('H:i') : null;
        $this->ajusteFim = $request->to_ts ? CarbonImmutable::parse($request->to_ts)->setTimezone($tz)->format('H:i') : null;
        $this->ajusteMotivo = $request->reason;
        $this->notify('info', 'Editando solicitação pendente. Faça os ajustes e salve.');
    }

    public function cancelarEdicao(): void
    {
        $tz = config('app.timezone');

        $this->reset(['editingRequestId', 'ajusteInicio', 'ajusteFim', 'ajusteMotivo']);
        $this->ajusteData = CarbonImmutable::now($tz)->format('Y-m-d');
    }

    public function removerSolicitacao(int $requestId): void
    {
        $user = $this->user();

        $request = AdjustRequest::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('status', AdjustRequest::STATUS_PENDENTE)
            ->findOrFail($requestId);

        $request->delete();

        if ($this->editingRequestId === $requestId) {
            $this->cancelarEdicao();
        }

        $this->notify('success', 'Solicitação removida.');

        $this->loadSolicitacoesRecentes();
    }

    public function getSolicitacoesFiltradasProperty()
    {
        if (! $this->recentRequests) {
            return collect();
        }

        $status = strtoupper($this->ajusteStatusFiltro);

        return $this->recentRequests
            ->when($status !== 'TODOS', fn ($collection) => $collection->where('status', $status))
            ->values();
    }

    private function calculateWorkedSeconds(array $punches): int
    {
        $seconds = 0;
        $currentStart = null;

        foreach ($punches as $punch) {
            /** @var CarbonImmutable|null $ts */
            $ts = $punch['ts_local'] ?? null;
            if (! $ts) {
                continue;
            }

            switch ($punch['type']) {
                case 'IN':
                case 'BREAK_OUT':
                    $currentStart = $ts;
                    break;
                case 'BREAK_IN':
                case 'OUT':
                    if ($currentStart) {
                        $seconds += max(0, $currentStart->diffInSeconds($ts));
                        $currentStart = null;
                    }
                    break;
            }
        }

        return $seconds;
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    private function summarizeAlertDays(array $dates, int $limit = 5): array
    {
        $unique = array_values(array_unique($dates));
        $sample = array_slice($unique, 0, $limit);

        $formattedSample = array_map(function (string $date) {
            try {
                $carbon = CarbonImmutable::createFromFormat('Y-m-d', $date, config('app.timezone'));
            } catch (\Throwable $exception) {
                return $date;
            }

            return Str::ucfirst($carbon->locale(app()->getLocale())->translatedFormat('d \d\e F'));
        }, $sample);

        $remaining = max(count($unique) - count($sample), 0);

        return [
            'total' => count($unique),
            'sample' => $formattedSample,
            'remaining' => $remaining,
        ];
    }

    private function availableMonths(): array
    {
        $months = [];
        $now = CarbonImmutable::now(config('app.timezone'));

        for ($i = 0; $i < 12; $i++) {
            $ref = $now->subMonths($i);
            $months[$ref->format('Y-m')] = Str::ucfirst($ref->translatedFormat('F Y'));
        }

        return $months;
    }

    private function user(): Authenticatable
    {
        return Auth::user();
    }
}
