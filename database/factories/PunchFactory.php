<?php

namespace Database\Factories;

use App\Models\Punch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Punch>
 */
class PunchFactory extends Factory
{
    protected $model = Punch::class;

    public function definition(): array
    {
        $timestamp = fake()->dateTimeBetween('-10 days', 'now', 'UTC');

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['IN', 'OUT', 'BREAK_IN', 'BREAK_OUT']),
            'ts_server' => $timestamp,
            'ts_client' => $timestamp,
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device_info' => [
                'platform' => fake()->randomElement(['Windows', 'Linux', 'MacOS']),
                'language' => fake()->locale(),
                'screen' => ['width' => 1920, 'height' => 1080],
                'timezone' => 'America/Maceio',
            ],
            'fingerprint_hash' => str_pad(bin2hex(random_bytes(16)), 64, 'a'),
            'geo' => ['lat' => -9.6489, 'lon' => -35.7089, 'accuracy_m' => 15],
            'geo_consent' => true,
            'observacao' => null,
            'source' => 'web',
        ];
    }
}
