<?php

namespace Database\Factories;

use App\Models\AdjustRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<AdjustRequest>
 */
class AdjustRequestFactory extends Factory
{
    protected $model = AdjustRequest::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d');

        return [
            'user_id' => User::factory(),
            'date' => $date,
            'from_ts' => null,
            'to_ts' => null,
            'reason' => fake()->sentence(8),
            'status' => AdjustRequest::STATUS_PENDENTE,
        ];
    }

    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AdjustRequest::STATUS_APROVADO,
                'approver_id' => User::factory(),
                'decided_at' => now('UTC'),
            ];
        });
    }
}
