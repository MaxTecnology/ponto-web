<?php

namespace App\Livewire\Rh;

use App\Models\AdjustRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Ajustes extends Component
{
    #[Validate('nullable|string|max:500')]
    public ?string $comentario = null;

    public ?int $selecionadoId = null;

    public string $acao = 'aprovar';

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }

    public function render()
    {
        $pendentes = $this->pendentes();
        $metrics = $this->metrics();
        $historico = $this->historicoRecentes();
        $selecionado = $this->selecionadoId
            ? AdjustRequest::with(['user', 'approver'])->find($this->selecionadoId)
            : null;

        return view('livewire.rh.ajustes', [
            'pendentes' => $pendentes,
            'metrics' => $metrics,
            'historico' => $historico,
            'selecionado' => $selecionado,
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
            $this->notify('warning', 'Solicitação já tratada anteriormente.');
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

        $this->notify('success', 'Solicitação atualizada com sucesso.');

        $this->reset(['selecionadoId', 'comentario']);
    }

    public function cancelar(): void
    {
        $this->reset(['selecionadoId', 'comentario']);
    }

    private function pendentes()
    {
        return AdjustRequest::query()
            ->with(['user'])
            ->where('status', AdjustRequest::STATUS_PENDENTE)
            ->orderBy('created_at')
            ->get();
    }

    private function historicoRecentes()
    {
        return AdjustRequest::query()
            ->with(['user', 'approver'])
            ->where('status', '!=', AdjustRequest::STATUS_PENDENTE)
            ->latest('decided_at')
            ->limit(8)
            ->get();
    }

    private function metrics(): array
    {
        $counts = AdjustRequest::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendentes = (int) ($counts[AdjustRequest::STATUS_PENDENTE] ?? 0);
        $aprovados = (int) ($counts[AdjustRequest::STATUS_APROVADO] ?? 0);
        $rejeitados = (int) ($counts[AdjustRequest::STATUS_REJEITADO] ?? 0);

        $tempoMedioMinutos = AdjustRequest::query()
            ->whereNotNull('decided_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, decided_at)) as media')
            ->value('media');

        return [
            'pendentes' => $pendentes,
            'aprovados' => $aprovados,
            'rejeitados' => $rejeitados,
            'tempo_medio' => $tempoMedioMinutos ? round($tempoMedioMinutos) : null,
        ];
    }
}
