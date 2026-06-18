<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['program_name' => 'Regular', 'kuota' => 8, 'duration'=> 2],
            ['program_name' => 'Intensif', 'kuota' => 12, 'duration'=> 3],
            ['program_name' => 'Non Regular', 'kuota' => 0, 'duration'=> 2],
        ];

        foreach ($programs as $program) {
            Program::updateOrCreate(
                ['program_name' => $program['program_name']],
                ['kuota' => $program['kuota'], 'duration' => $program['duration']]
            );
        }
    }
}
