<?php

namespace Database\Seeders;

use App\Models\IncomeCategory;
use App\Models\Scholarship;
use App\Models\StudyField;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('Fictional demo accounts must not be seeded into production.');
        }
        DB::transaction(function () {
            $admin = User::updateOrCreate(['email' => 'admin@scholarmatch.test'], ['name' => 'Demo Administrator', 'role' => 'admin', 'is_active' => true, 'password' => 'Demo!123456']);
            $fields = collect(['Computer Science', 'Engineering', 'Business', 'Medicine', 'Arts & Humanities'])->map(fn ($name) => StudyField::firstOrCreate(['name' => $name], ['is_active' => true]));
            foreach ([['B40', 0, 4999.99], ['M40', 5000, 9999.99], ['T20', 10000, null]] as [$name,$minimum,$maximum]) {
                IncomeCategory::updateOrCreate(['name' => $name, 'reference_year' => 2026], ['minimum' => $minimum, 'maximum' => $maximum, 'source_url' => 'https://example.org/fictional-income-ranges', 'is_active' => true]);
            }
            for ($i = 1; $i <= 10; $i++) {
                $user = User::updateOrCreate(['email' => 'student'.$i.'@scholarmatch.test'], ['name' => $i === 1 ? 'Aina Binti Razak' : 'Demo Student '.$i, 'role' => 'student', 'password' => 'Demo!123456', 'is_active' => true]);
                $profile = $user->profile()->updateOrCreate([], ['citizenship' => $i === 9 ? 'Non-Malaysian' : 'Malaysian', 'residence_state' => 'Selangor', 'current_education_level' => 'diploma', 'funding_level' => 'bachelor', 'study_field_id' => $fields[0]->id, 'study_field_undecided' => false, 'enrolment_status' => $i % 3 === 0 ? 'enrolled' : 'planning', 'institution_type' => 'public_university', 'study_location' => 'Malaysia', 'household_income' => $i === 10 ? null : ($i === 1 ? 3200 : $i * 1000), 'academic_records_complete' => $i !== 10]);
                $profile->academicRecords()->delete();
                $profile->academicRecords()->createMany([
                    ['qualification' => 'SPM', 'completion_status' => 'completed', 'result_type' => 'as_count', 'result_value' => 7, 'grading_scale' => 10, 'completion_year' => 2023],
                    ['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'result_value' => $i === 1 ? 3.8 : 3.0 + $i / 10, 'grading_scale' => 4, 'completion_year' => 2026],
                ]);
            }
            $titles = ['Future Innovators Award', 'Academic Excellence Scholarship', 'Open Horizons Bursary', 'Community Leaders Grant', 'STEM Access Scholarship', 'Next Chapter Fellowship', 'Regional Education Award', 'Global Learning Scholarship'];
            $providers = ['Demo Meranti Foundation', 'Demo Cendekia Trust', 'Demo Horizon Education', 'Demo Bayu Foundation'];
            for ($i = 0; $i < 40; $i++) {
                $group = $i % 8;
                $criteria = array_fill_keys(array_keys(config('scholarmatch.criteria')), ['mode' => 'none']);
                $criteria['citizenship'] = ['mode' => 'restricted', 'values' => ['Malaysian']];
                $criteria['funding_level'] = ['mode' => 'restricted', 'values' => ['bachelor']];
                if (in_array($group, [0, 1, 4, 5])) {
                    $criteria['academic'] = ['mode' => 'restricted'];
                }
                if ($group === 3) {
                    $criteria['additional'] = ['mode' => 'manual', 'notes' => 'The provider must review your community leadership experience.'];
                }
                if ($group === 4) {
                    $criteria['income'] = ['mode' => 'restricted', 'kind' => 'numeric', 'maximum' => 5000];
                }
                if ($group === 6) {
                    $criteria['residence_state'] = ['mode' => 'restricted', 'values' => ['Sabah', 'Sarawak']];
                }
                if ($group === 7) {
                    $criteria['enrolment_status'] = ['mode' => 'restricted', 'values' => ['offer_received', 'enrolled']];
                }
                $s = Scholarship::updateOrCreate(['name' => 'Demo '.$titles[$group].' '.(intdiv($i, 8) + 1)], ['provider' => $providers[$i % 4], 'description' => 'Fictional demonstration opportunity supporting ambitious Malaysian students. This record is for coursework and does not represent a real scholarship.', 'benefits' => $group === 2 ? 'Tuition support and learning materials' : ($group === 0 ? 'RM 45,000 / year' : 'Full tuition + living allowance'), 'deadline' => now()->addDays(($i % 15) + 2)->toDateString(), 'application_url' => 'https://example.org/demo-application', 'source_url' => 'https://example.org/demo-scholarship-source', 'status' => $i >= 35 ? 'inactive' : ($i >= 30 ? 'closed' : 'open'), 'source_status' => 'reviewed', 'last_verified_at' => $i % 9 === 0 ? now()->subDays(100) : now(), 'verified_by' => $admin->id, 'is_fictional' => true]);
                $s->requirements()->updateOrCreate([], ['criteria' => $criteria]);
                $s->academicRoutes()->delete();
                $s->studyFields()->sync([]);
                $s->incomeCategories()->sync([]);
                if (in_array($group, [0, 1, 4, 5])) {
                    $s->academicRoutes()->createMany([
                        ['qualification' => 'SPM', 'completion_status' => 'completed', 'result_type' => 'as_count', 'minimum' => 8, 'grading_scale' => 10],
                        ['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => $group === 0 ? 3 : ($group === 5 ? 3.9 : 3.5), 'grading_scale' => 4],
                    ]);
                }
            }
        });
    }
}
