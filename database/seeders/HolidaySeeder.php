<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['scope' => 'NACIONAL', 'date' => '2025-01-01', 'name' => 'Confraternização Universal'],
            ['scope' => 'NACIONAL', 'date' => '2025-04-21', 'name' => 'Tiradentes'],
            ['scope' => 'NACIONAL', 'date' => '2025-09-07', 'name' => 'Independência do Brasil'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['date' => $holiday['date'], 'name' => $holiday['name']],
                $holiday,
            );
        }
    }
}
