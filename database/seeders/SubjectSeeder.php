<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            'Matematika',
            'Bahasa Inggris',
        ];

        foreach ($subjects as $name) {
            Subject::firstOrCreate(['subject_name' => $name]);
        }
    }
}
