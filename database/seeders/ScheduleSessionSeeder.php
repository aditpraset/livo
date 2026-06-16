<?php

namespace Database\Seeders;

use App\Models\ScheduleSession;
use Illuminate\Database\Seeder;

class ScheduleSessionSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = [
            ['name' => 'Sesi 1',   'time_start' => '09:00:00', 'time_end' => '10:30:00'],
            ['name' => 'Sesi 2',   'time_start' => '13:00:00', 'time_end' => '14:30:00'],
            ['name' => 'Sesi 3',   'time_start' => '14:30:00', 'time_end' => '16:00:00'],
            ['name' => 'Sesi 4',   'time_start' => '16:00:00', 'time_end' => '17:30:00'],
            ['name' => 'Sesi 5',   'time_start' => '18:30:00', 'time_end' => '20:00:00'],
            ['name' => 'Sesi 1-K', 'time_start' => '09:30:00', 'time_end' => '10:30:00'],
            ['name' => 'Sesi 2-K', 'time_start' => '13:00:00', 'time_end' => '14:00:00'],
            ['name' => 'Sesi 3-K', 'time_start' => '14:00:00', 'time_end' => '15:00:00'],
            ['name' => 'Sesi 4-K', 'time_start' => '15:00:00', 'time_end' => '16:00:00'],
            ['name' => 'Sesi 5-K', 'time_start' => '16:00:00', 'time_end' => '17:00:00'],
        ];

        foreach ($sessions as $session) {
            ScheduleSession::firstOrCreate(
                ['name' => $session['name']],
                ['time_start' => $session['time_start'], 'time_end' => $session['time_end']]
            );
        }
    }
}
