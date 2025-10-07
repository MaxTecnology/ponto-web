<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Punch;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
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

        $inicio = $request->string('inicio')->toString() ?: null;
        $fim = $request->string('fim')->toString() ?: null;
        $busca = $request->string('busca')->toString() ?: null;
        $tipo = $request->string('tipo')->toString() ?: null;
        $semGeo = $request->boolean('sem_geo', false);
        $ipNovo = $request->boolean('ip_novo', false);
        $fingerprintNovo = $request->boolean('fingerprint_novo', false);

        $query->when($inicio, function (Builder $builder) use ($inicio): void {
            $builder->where('punches.ts_server', '>=', $inicio . ' 00:00:00');
        });

        $query->when($fim, function (Builder $builder) use ($fim): void {
            $builder->where('punches.ts_server', '<=', $fim . ' 23:59:59');
        });

        $query->when($busca, function (Builder $builder) use ($busca): void {
            $builder->whereHas('user', function (Builder $sub) use ($busca): void {
                $sub->where('name', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            });
        });

        $query->when($tipo, function (Builder $builder) use ($tipo): void {
            $builder->where('punches.type', $tipo);
        });

        if ($semGeo) {
            $query->having('sem_geo_flag', '=', 1);
        }

        if ($ipNovo) {
            $query->having('ip_novo_flag', '=', 1);
        }

        if ($fingerprintNovo) {
            $query->having('fingerprint_novo_flag', '=', 1);
        }

        $punches = $query->orderBy('punches.ts_server')->get();
        $timezone = config('app.timezone');

        $filename = 'export_pontos_' . CarbonImmutable::now('UTC')->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = [
            'punch_id', 'user_id', 'nome', 'email', 'role', 'data_local', 'ts_server_utc', 'ts_local',
            'tipo', 'ip', 'user_agent', 'geo_lat', 'geo_lon', 'accuracy_m',
            'sem_geo', 'ip_novo', 'fingerprint_novo', 'observacao',
        ];

        $callback = static function () use ($punches, $columns, $timezone): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns, ';');

            foreach ($punches as $punch) {
                $tsLocal = Timezone::toLocal($punch->ts_server, $timezone);
                $geo = $punch->geo ?? [];

                fputcsv($handle, [
                    $punch->id,
                    $punch->user->id,
                    $punch->user->name,
                    $punch->user->email,
                    $punch->user->role,
                    $tsLocal?->format('Y-m-d'),
                    Timezone::toLocal($punch->ts_server, 'UTC')?->format('c'),
                    $tsLocal?->format('c'),
                    $punch->type,
                    $punch->ip,
                    $punch->user_agent,
                    $geo['lat'] ?? null,
                    $geo['lon'] ?? null,
                    $geo['accuracy_m'] ?? null,
                    $punch->sem_geo_flag ? 'true' : 'false',
                    $punch->ip_novo_flag ? 'true' : 'false',
                    $punch->fingerprint_novo_flag ? 'true' : 'false',
                    $punch->observacao,
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
