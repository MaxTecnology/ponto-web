<?php

namespace App\Livewire\Rh;

use App\Models\AdjustRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Ajustes extends Component
{
    #[Validate('nullable|string|max:500')]
    public ?string $comentario = null;

    public ?int $selecionadoId = null;

    public string $acao = 'aprovar';

    public function render()
    {
        $pendentes = AdjustRequest::query()
            ->with(['user'])
            ->where('status', AdjustRequest::STATUS_PENDENTE)
            ->orderBy('created_at')
            ->get();

        return view('livewire.rh.ajustes', [
            'pendentes' => $pendentes,
        ]);
    }

    public function selecionar(int $id, string $acao): void
    {
        $this->selecionadoId = $id;
        $this->acao = $acao;
        $this->comentario = null;
    }

    public function processar(): void
    {
        $this->validate();

        if (! $this->selecionadoId) {
            return;
        }

        $ajuste = AdjustRequest::with('user')->findOrFail($this->selecionadoId);

        if ($ajuste->status !== AdjustRequest::STATUS_PENDENTE) {
            session()->flash('status', 'Solicitação já tratada anteriormente.');
            $this->reset(['selecionadoId', 'comentario']);
            return;
        }

        $status = $this->acao === 'aprovar' ? AdjustRequest::STATUS_APROVADO : AdjustRequest::STATUS_REJEITADO;

        $audit = $ajuste->audit ?? [];
        $audit[] = [
            'action' => $status,
            'comment' => $this->comentario,
            'by' => Auth::id(),
            'at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];

        $ajuste->update([
            'status' => $status,
            'approver_id' => Auth::id(),
            'decided_at' => CarbonImmutable::now('UTC'),
            'audit' => $audit,
        ]);

        session()->flash('status', 'Solicitação atualizada com sucesso.');

        $this->reset(['selecionadoId', 'comentario']);
    }

    public function cancelar(): void
    {
        $this->reset(['selecionadoId', 'comentario']);
    }
}
