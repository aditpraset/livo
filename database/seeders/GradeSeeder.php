<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = ['TK', 'SD', 'SMP', 'SMA'];

        foreach ($grades as $name) {
            Grade::firstOrCreate(['grade_name' => $name]);
        }
    }
}
