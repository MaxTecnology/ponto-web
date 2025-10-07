<?php

namespace Tests\Feature;

use App\Livewire\Ponto\BaterPonto;
use App\Livewire\Ponto\MeuEspelho;
use App\Livewire\Rh\Ajustes;
use App\Livewire\Rh\Dashboard;
use App\Models\AdjustRequest;
use App\Models\Punch;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PontoFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'ponto'], ['value' => ['min_interval_minutes' => 2]]);
    }

    public function test_colaborador_registra_batida_in(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COLABORADOR]);
        $this->actingAs($user);

        $this->app['request']->server->set('REMOTE_ADDR', '10.0.0.1');
        $this->app['request']->headers->set('User-Agent', 'PHPUnit');

        Livewire::test(BaterPonto::class)
            ->set('type', 'IN')
            ->set('clientPayload', [
                'ts_client' => CarbonImmutable::now('UTC')->toIso8601String(),
                'device_info' => ['platform' => 'TestOS', 'language' => 'pt-BR', 'screen' => ['width' => 1920, 'height' => 1080], 'timezone' => 'America/Maceio'],
                'fingerprint_hash' => str_repeat('a', 64),
                'geo' => ['lat' => -9.6, 'lon' => -35.7, 'accuracy_m' => 25],
                'geo_consent' => true,
            ])
            ->call('salvar')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('punches', 1);
        $punch = Punch::first();

        $this->assertEquals('IN', $punch->type);
        $this->assertNotEmpty($punch->ip);
        $this->assertNotEmpty($punch->user_agent);
        $this->assertEquals(config('app.timezone'), $punch->ts_server->getTimezone()->getName());
    }

    public function test_batida_sem_geo_eh_bloqueada(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COLABORADOR]);
        $this->actingAs($user);

        Livewire::test(BaterPonto::class)
            ->set('type', 'IN')
            ->set('clientPayload', [
                'ts_client' => CarbonImmutable::now('UTC')->toIso8601String(),
                'device_info' => ['platform' => 'TestOS', 'language' => 'pt-BR', 'screen' => ['width' => 1280, 'height' => 720], 'timezone' => 'America/Maceio'],
                'fingerprint_hash' => str_repeat('b', 64),
                'geo' => null,
                'geo_consent' => false,
            ])
            ->call('salvar')
            ->assertHasErrors(['type']);

        $this->assertDatabaseCount('punches', 0);
    }

    public function test_rh_dashboard_filtros(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_RH_MANAGER]);
        $target = User::factory()->create(['name' => 'Maria Silva', 'email' => 'maria@example.com']);
        $other = User::factory()->create();

        Punch::factory()->for($target)->create([
            'type' => 'IN',
            'ts_server' => CarbonImmutable::now('UTC')->subDay(),
            'geo' => null,
            'geo_consent' => false,
            'ip' => '200.10.0.1',
            'fingerprint_hash' => str_repeat('c', 64),
        ]);

        Punch::factory()->for($target)->create([
            'type' => 'OUT',
            'ts_server' => CarbonImmutable::now('UTC')->subDay()->addHours(8),
            'geo' => ['lat' => -9.6, 'lon' => -35.7, 'accuracy_m' => 50],
            'geo_consent' => true,
            'ip' => '200.10.0.1',
            'fingerprint_hash' => str_repeat('c', 64),
        ]);

        Punch::factory()->for($other)->create();

        $this->actingAs($manager);

        Livewire::test(Dashboard::class)
            ->set('startDate', CarbonImmutable::now('America/Maceio')->subDay()->format('Y-m-d'))
            ->set('endDate', CarbonImmutable::now('America/Maceio')->format('Y-m-d'))
            ->set('userSearch', 'Maria')
            ->set('flagIpNovo', true)
            ->assertSee('Maria Silva')
            ->assertSee('ip_novo');
    }

    public function test_fluxo_ajuste_aprovado(): void
    {
        $colaborador = User::factory()->create(['role' => User::ROLE_COLABORADOR]);
        $gestor = User::factory()->create(['role' => User::ROLE_RH_MANAGER]);

        $this->actingAs($colaborador);

        Livewire::test(MeuEspelho::class)
            ->set('ajusteData', CarbonImmutable::now('America/Maceio')->format('Y-m-d'))
            ->set('ajusteMotivo', 'Esqueci de sair no horário correto')
            ->call('solicitarAjuste');

        $ajuste = AdjustRequest::first();
        $this->assertEquals(AdjustRequest::STATUS_PENDENTE, $ajuste->status);

        $this->actingAs($gestor);

        Livewire::test(Ajustes::class)
            ->call('selecionar', $ajuste->id, 'aprovar')
            ->set('comentario', 'OK')
            ->call('processar');

        $ajuste->refresh();
        $this->assertEquals(AdjustRequest::STATUS_APROVADO, $ajuste->status);
        $this->assertEquals($gestor->id, $ajuste->approver_id);
        $this->assertNotNull($ajuste->decided_at);
    }

    public function test_export_csv_respeita_filtros(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_RH_MANAGER]);
        $target = User::factory()->create(['name' => 'João Export']);

        Punch::factory()->for($target)->create([
            'type' => 'IN',
            'ts_server' => CarbonImmutable::now('UTC')->subHours(2),
            'ip' => '201.0.0.1',
        ]);

        $this->actingAs($manager);

        $response = $this->get(route('rh.export', [
            'inicio' => CarbonImmutable::now('America/Maceio')->subDay()->format('Y-m-d'),
            'fim' => CarbonImmutable::now('America/Maceio')->format('Y-m-d'),
            'busca' => 'João',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('-03:00', $response->streamedContent());
    }

    public function test_rbac_restringe_rotas_rh(): void
    {
        $colaborador = User::factory()->create(['role' => User::ROLE_COLABORADOR]);
        $rh = User::factory()->create(['role' => User::ROLE_RH_MANAGER]);

        $this->actingAs($colaborador);
        $this->get('/rh/ponto')->assertForbidden();

        $this->actingAs($rh);
        $this->get('/rh/ponto')->assertOk();
    }

    public function test_intervalo_minimo_impede_batida_rapida(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COLABORADOR]);
        Punch::factory()->for($user)->create([
            'ts_server' => CarbonImmutable::now('UTC')->subMinute(),
        ]);

        $this->actingAs($user);

        Livewire::test(BaterPonto::class)
            ->set('type', 'IN')
            ->set('clientPayload', [
                'ts_client' => CarbonImmutable::now('UTC')->toIso8601String(),
                'device_info' => ['platform' => 'TestOS', 'language' => 'pt-BR', 'screen' => ['width' => 1920, 'height' => 1080], 'timezone' => 'America/Maceio'],
                'fingerprint_hash' => str_repeat('d', 64),
                'geo' => ['lat' => -9.6, 'lon' => -35.7, 'accuracy_m' => 15],
                'geo_consent' => true,
            ])
            ->call('salvar')
            ->assertHasErrors(['type']);
    }
}
