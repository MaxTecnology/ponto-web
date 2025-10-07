<?php

namespace App\Livewire\Rh;

use App\Models\AdjustRequest;
use App\Models\Punch;
use App\Models\Setting;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Fechamento extends Component
{
    #[Validate('required|date')]
    public string $startDate;

    #[Validate('required|date|after_or_equal:startDate')]
    public string $endDate;

    public array $missingExits = [];

    public int $pendingAdjustments = 0;

    public array $fechamentos = [];

    public function mount(): void
    {
        $now = CarbonImmutable::now(config('app.timezone'))->startOfMonth();
        $this->startDate = $now->format('Y-m-d');
        $this->endDate = $now->endOfMonth()->format('Y-m-d');

        $this->loadInsights();
        $this->loadFechamentos();
    }

    public function render()
    {
        return view('livewire.rh.fechamento');
    }

    public function updatedStartDate(): void
    {
        $this->validateOnly('startDate');
        $this->loadInsights();
    }

    public function updatedEndDate(): void
    {
        $this->validateOnly('endDate');
        $this->loadInsights();
    }

    public function fecharPeriodo(): void
    {
        $this->validate();

        $setting = Setting::firstOrCreate(['key' => 'ponto_fechamentos'], ['value' => []]);
        $lista = $setting->value ?? [];

        $registro = [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'closed_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            'closed_by' => Auth::id(),
        ];

        $lista[] = $registro;

        $setting->value = $lista;
        $setting->save();

        session()->flash('status', 'Período fechado logicamente. Registre export e arquive os relatórios.');

        $this->loadFechamentos();
    }

    private function loadFechamentos(): void
    {
        $registros = Setting::value('ponto_fechamentos', []);
        $userIds = array_filter(array_column($registros, 'closed_by'));

        $users = $userIds
            ? \App\Models\User::query()->whereIn('id', $userIds)->pluck('name', 'id')
            : collect();

        $this->fechamentos = array_map(function (array $registro) use ($users) {
            $registro['closed_by_name'] = isset($registro['closed_by'])
                ? ($users[$registro['closed_by']] ?? 'Usuário #' . $registro['closed_by'])
                : null;

            return $registro;
        }, $registros ?? []);
    }

    private function loadInsights(): void
    {
        $tz = config('app.timezone');
        $inicioLocal = CarbonImmutable::createFromFormat('Y-m-d', $this->startDate, $tz)->startOfDay();
        $fimLocal = CarbonImmutable::createFromFormat('Y-m-d', $this->endDate, $tz)->endOfDay();

        $inicioUtc = $inicioLocal->timezone('UTC');
        $fimUtc = $fimLocal->timezone('UTC');

        $this->pendingAdjustments = AdjustRequest::query()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', AdjustRequest::STATUS_PENDENTE)
            ->count();

        $punches = Punch::query()
            ->with('user')
            ->whereBetween('ts_server', [$inicioUtc, $fimUtc])
            ->orderBy('user_id')
            ->orderBy('ts_server')
            ->get();

        $agrupados = [];
        foreach ($punches as $punch) {
            if (! $punch->ts_server) {
                continue;
            }

            $tsLocal = Timezone::toLocal($punch->ts_server, $tz);
            $dia = $tsLocal->format('Y-m-d');

            $agrupados[$punch->user_id][$dia][] = [
                'type' => $punch->type,
                'ts_local' => $tsLocal,
                'user' => $punch->user,
            ];
        }

        $faltantes = [];
        foreach ($agrupados as $userId => $dias) {
            foreach ($dias as $data => $lista) {
                $ultimo = end($lista);
                if (! $ultimo) {
                    continue;
                }

                if ($ultimo['type'] !== 'OUT') {
                    $faltantes[] = [
                        'user' => $ultimo['user'],
                        'date' => $data,
                        'last_type' => $ultimo['type'],
                        'last_time' => $ultimo['ts_local'],
                    ];
                }
            }
        }

        usort($faltantes, static function (array $a, array $b): int {
            return strcmp($a['date'], $b['date']);
        });

        $this->missingExits = $faltantes;
    }
}
