<?php

namespace App\Livewire\Ponto;

use App\Models\AdjustRequest;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

class MeuEspelho extends Component
{
    public string $month;

    public array $report = [];

    public int $totalSeconds = 0;

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

    public ?string $flashMessage = null;

    /** @var \Illuminate\Support\Collection<int, AdjustRequest> */
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
            'flashMessage' => $this->flashMessage,
        ]);
    }

    public function updatedMonth(): void
    {
        $this->flashMessage = null;
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

            session()->flash('status', 'Solicitação de ajuste atualizada.');
            $this->flashMessage = 'Solicitação de ajuste atualizada.';
        } else {
            AdjustRequest::create([
                'user_id' => $user->getAuthIdentifier(),
                'date' => $this->ajusteData,
                'from_ts' => $from,
                'to_ts' => $to,
                'reason' => $this->ajusteMotivo,
            ]);

            session()->flash('status', 'Solicitação de ajuste enviada para aprovação.');
            $this->flashMessage = 'Solicitação de ajuste enviada para aprovação.';
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
        }

        ksort($days);

        foreach ($days as &$day) {
            $day['worked_seconds'] = $this->calculateWorkedSeconds($day['punches']);
            foreach ($day['punches'] as &$punch) {
                unset($punch['flags']);
            }
            $totalSeconds += $day['worked_seconds'];
        }

        $this->report = $days;
        $this->totalSeconds = $totalSeconds;

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
            ->take(10)
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
        $this->flashMessage = 'Editando solicitação pendente. Faça os ajustes e salve.';
    }

    public function cancelarEdicao(): void
    {
        $tz = config('app.timezone');

        $this->reset(['editingRequestId', 'ajusteInicio', 'ajusteFim', 'ajusteMotivo']);
        $this->ajusteData = CarbonImmutable::now($tz)->format('Y-m-d');
        $this->flashMessage = null;
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

        session()->flash('status', 'Solicitação removida.');
        $this->flashMessage = 'Solicitação removida.';

        $this->loadSolicitacoesRecentes();
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
