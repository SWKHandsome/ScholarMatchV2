<?php

namespace Database\Seeders;

use App\Models\StudyField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudyFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            foreach (config('scholarmatch.study_fields') as $name) {
                StudyField::firstOrCreate(['name' => $name], ['is_active' => true]);
            }
        });
    }
}
