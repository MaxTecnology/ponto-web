<?php

namespace App\Livewire\Ponto;

use App\Models\Punch;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

class BaterPonto extends Component
{
    #[Validate('required|string|in:IN,OUT,BREAK_IN,BREAK_OUT')]
    public string $type = 'IN';

    #[Validate('nullable|string|max:255')]
    public ?string $observacao = null;

    /**
     * Dados enviados pelo cliente (geo/device/fingerprint).
     */
    public array $clientPayload = [];

    public ?Punch $lastPunch = null;

    /**
     * @var array<string, string>
     */
    public array $tipoLabels = [
        'IN' => 'Entrada',
        'OUT' => 'Saída',
        'BREAK_IN' => 'Início Pausa',
        'BREAK_OUT' => 'Fim Pausa',
    ];

    public function mount(): void
    {
        $this->loadLastPunch();
    }

    public function render()
    {
        return view('livewire.ponto.bater-ponto');
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }

    public function salvar(): void
    {
        Log::debug('ponto.payload_recebido', ['payload' => $this->clientPayload]);
        $this->validate();

        $payload = $this->clientPayload;

        if (! is_array($payload)) {
            $this->addError('type', 'Erro ao capturar dados do dispositivo. Tente novamente.');
            $this->notify('error', 'Erro ao capturar dados do dispositivo. Atualize a página e tente novamente.');
            return;
        }

        $user = $this->user();
        $now = CarbonImmutable::now('UTC');
        $minInterval = $this->minIntervalMinutes();

        $ultimoRegistro = $user->punches()->latest('ts_server')->first();
        if ($minInterval > 0 && $ultimoRegistro && $ultimoRegistro->ts_server instanceof \Carbon\CarbonInterface) {
            $rawTs = $ultimoRegistro->getRawOriginal('ts_server');
            $lastUtc = $rawTs
                ? CarbonImmutable::parse($rawTs, 'UTC')
                : CarbonImmutable::parse($ultimoRegistro->ts_server->format('Y-m-d H:i:s.u'), config('app.timezone'))->setTimezone('UTC');

            $diffMinutes = $lastUtc->diffInMinutes($now, false);
            Log::debug('ponto.interval_check', [
                'last_ts' => $lastUtc->toIso8601String(),
                'now' => $now->toIso8601String(),
                'diff_minutes' => $diffMinutes,
                'min_interval' => $minInterval,
            ]);

            if ($diffMinutes >= 0 && $diffMinutes < $minInterval) {
                $this->addError('type', "É necessário aguardar {$minInterval} minutos entre batidas.");
                $this->notify('warning', "Aguarde {$minInterval} minutos entre batidas para evitar duplicidade.");
                return;
            }
        }

        $tsClient = null;
        if (! empty($payload['ts_client'])) {
            try {
                $tsClient = CarbonImmutable::parse($payload['ts_client'])->utc();
            } catch (\Throwable $exception) {
                Log::warning('Timestamp do cliente inválido ao bater ponto', [
                    'user_id' => $user->getAuthIdentifier(),
                    'ts_client' => $payload['ts_client'],
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $deviceInfo = $payload['device_info'] ?? null;
        if ($deviceInfo && ! is_array($deviceInfo)) {
            $deviceInfo = null;
        }

        $geo = $payload['geo'] ?? null;
        if ($geo && ! is_array($geo)) {
            $geo = null;
        }

        if (empty($geo) || ! ($payload['geo_consent'] ?? false)) {
            $this->addError('type', 'Para registrar o ponto é necessário permitir a coleta de localização.');
            $this->notify('error', 'Não foi possível registrar: habilite a localização do navegador e tente novamente.');
            return;
        }

        $punch = Punch::create([
            'user_id' => $user->getAuthIdentifier(),
            'type' => $this->type,
            'ts_server' => $now,
            'ts_client' => $tsClient,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_info' => $deviceInfo,
            'fingerprint_hash' => isset($payload['fingerprint_hash']) && is_string($payload['fingerprint_hash'])
                ? Str::limit($payload['fingerprint_hash'], 64, '')
                : null,
            'geo' => $geo,
            'geo_consent' => (bool) ($payload['geo_consent'] ?? false),
            'observacao' => $this->observacao ? trim($this->observacao) : null,
            'source' => 'web',
        ]);

        $this->reset(['observacao']);
        $this->clientPayload = [];
        $this->loadLastPunch();

        session()->flash('status', 'Ponto registrado com sucesso.');
    }

    private function user(): Authenticatable
    {
        return Auth::user();
    }

    private function minIntervalMinutes(): int
    {
        $config = Setting::value('ponto') ?? [];

        return (int) ($config['min_interval_minutes'] ?? 0);
    }

    private function loadLastPunch(): void
    {
        $user = $this->user();

        $this->lastPunch = $user
            ->punches()
            ->whereDate('ts_server', CarbonImmutable::now('UTC')->toDateString())
            ->latest('ts_server')
            ->first();
    }
}
