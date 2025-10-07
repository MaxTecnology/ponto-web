<?php

namespace App\Livewire\Rh;

use App\Models\Punch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    /**
     * @var array<string, string>
     */
    public array $tipoLabels = [
        'IN' => 'Entrada',
        'OUT' => 'Saída',
        'BREAK_IN' => 'Início Pausa',
        'BREAK_OUT' => 'Fim Pausa',
    ];

    #[Url(as: 'inicio')]
    public ?string $startDate = null;

    #[Url(as: 'fim')]
    public ?string $endDate = null;

    #[Url(as: 'busca')]
    public string $userSearch = '';

    #[Url(as: 'tipo')]
    public ?string $type = null;

    #[Url(as: 'sem_geo')]
    public bool $flagSemGeo = false;

    #[Url(as: 'ip_novo')]
    public bool $flagIpNovo = false;

    #[Url(as: 'fingerprint_novo')]
    public bool $flagFingerprintNovo = false;

    public int $perPage = 15;

    protected $queryString = ['page' => ['except' => 1]];

    public function render()
    {
        return view('livewire.rh.dashboard', [
            'punches' => $this->punches(),
            'tipoLabels' => $this->tipoLabels,
        ]);
    }

    public function updated($name, $value): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    private function punches(): LengthAwarePaginator
    {
        $query = Punch::query()
            ->with('user')
            ->select('punches.*')
            ->selectRaw('IF(punches.geo IS NULL OR punches.geo_consent = 0, 1, 0) AS sem_geo_flag')
            ->selectRaw('NOT EXISTS (
                SELECT 1
                FROM punches AS p2
                WHERE p2.user_id = punches.user_id
                    AND p2.id <> punches.id
                    AND p2.ip = punches.ip
                    AND p2.ip IS NOT NULL
                    AND p2.ts_server < punches.ts_server
                    AND p2.ts_server >= DATE_SUB(punches.ts_server, INTERVAL 30 DAY)
            ) AS ip_novo_flag')
            ->selectRaw('NOT EXISTS (
                SELECT 1
                FROM punches AS p3
                WHERE p3.user_id = punches.user_id
                    AND p3.id <> punches.id
                    AND p3.fingerprint_hash IS NOT NULL
                    AND p3.fingerprint_hash = punches.fingerprint_hash
                    AND p3.ts_server < punches.ts_server
                    AND p3.ts_server >= DATE_SUB(punches.ts_server, INTERVAL 30 DAY)
            ) AS fingerprint_novo_flag');

        $query->when($this->startDate, function (Builder $builder): void {
            $builder->where('punches.ts_server', '>=', $this->startDate . ' 00:00:00');
        });

        $query->when($this->endDate, function (Builder $builder): void {
            $builder->where('punches.ts_server', '<=', $this->endDate . ' 23:59:59');
        });

        $query->when($this->userSearch, function (Builder $builder): void {
            $builder->whereHas('user', function (Builder $userQuery): void {
                $userQuery->where(function (Builder $sub): void {
                    $sub->where('name', 'like', '%' . $this->userSearch . '%')
                        ->orWhere('email', 'like', '%' . $this->userSearch . '%');
                });
            });
        });

        $query->when($this->type, function (Builder $builder): void {
            $builder->where('punches.type', $this->type);
        });

        if ($this->flagSemGeo) {
            $query->having('sem_geo_flag', '=', 1);
        }

        if ($this->flagIpNovo) {
            $query->having('ip_novo_flag', '=', 1);
        }

        if ($this->flagFingerprintNovo) {
            $query->having('fingerprint_novo_flag', '=', 1);
        }

        return $query
            ->orderByDesc('punches.ts_server')
            ->paginate($this->perPage);
    }
}
