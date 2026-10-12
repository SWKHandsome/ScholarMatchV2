<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\StudentAcademicRecord;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $p = StudentProfile::factory()->create();
        StudentAcademicRecord::factory()->create(['student_profile_id' => $p->id]);

        return User::findOrFail($p->user_id);
    }

    private function scholarship(?float $minimum = null, array $criteria = [], array $attributes = []): Scholarship
    {
        $s = Scholarship::factory()->create($attributes);
        if ($minimum !== null) {
            $criteria['academic'] = ['mode' => 'restricted'];
            $s->academicRoutes()->create(['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => $minimum, 'grading_scale' => 4]);
        }
        $s->requirements->update(['criteria' => $criteria + $s->requirements->criteria]);

        return $s;
    }

    public function test_default_order_prioritizes_scores_then_na_then_manual_then_failures(): void
    {
        $u = $this->user();
        $na = $this->scholarship(null, [], ['deadline' => now()->addDay()->toDateString()]);
        $manual = $this->scholarship(null, ['additional' => ['mode' => 'manual', 'notes' => 'Interview']]);
        $fail = $this->scholarship(null, ['citizenship' => ['mode' => 'restricted', 'values' => ['Non-Malaysian']]]);
        $low = $this->scholarship(3.5);
        $high = $this->scholarship(3);
        $service = app(RecommendationService::class);
        $this->assertSame([$high->id, $low->id, $na->id, $manual->id, $fail->id], $service->order($service->forUser($u))->pluck('scholarship.id')->all());
    }

    public function test_equal_margins_use_deadline_then_identifier_and_sort_before_pagination(): void
    {
        $u = $this->user();
        $expected = [];
        for ($i = 0; $i < 24; $i++) {
            $s = $this->scholarship(3.5, [], ['deadline' => now()->addDays(25 - $i)->toDateString()]);
            $expected[] = $s->id;
        }
        $service = app(RecommendationService::class);
        $rows = $service->order($service->forUser($u));
        $this->assertSame(array_reverse($expected), $rows->pluck('scholarship.id')->all());
        $this->assertSame(array_slice(array_reverse($expected), 10, 10), $service->paginate($rows, 2)->getCollection()->pluck('scholarship.id')->all());
        $a = $this->scholarship(3);
        $b = $this->scholarship(3);
        $ids = $service->order($service->forUser($u))->take(2)->pluck('scholarship.id')->all();
        $this->assertSame([$a->id, $b->id], $ids);
    }

    public function test_alternative_sorts_preserve_eligibility_groups(): void
    {
        $u = $this->user();
        $fail = $this->scholarship(null, ['citizenship' => ['mode' => 'restricted', 'values' => ['Non-Malaysian']]], ['name' => 'A failure', 'deadline' => now()->addDay()->toDateString()]);
        $eligible = $this->scholarship(3.5, [], ['name' => 'Z eligible']);
        $na = $this->scholarship(null, [], ['name' => 'B unscored', 'deadline' => now()->addDays(2)->toDateString()]);
        $service = app(RecommendationService::class);
        $rows = $service->forUser($u);
        $this->assertSame([$na->id, $eligible->id, $fail->id], $service->order($rows, 'name')->pluck('scholarship.id')->all());
        $this->assertSame([$na->id, $eligible->id, $fail->id], $service->order($rows, 'deadline')->pluck('scholarship.id')->all());
        $this->assertSame($fail->id, $service->order($rows, 'recent')->last()['scholarship']->id);
    }

    public function test_filters_default_open_and_evaluations_refresh_after_profile_and_scholarship_updates(): void
    {
        $u = $this->user();
        $s = $this->scholarship(3.5, [], ['provider' => 'STEM Demo']);
        $closed = $this->scholarship(null, [], ['deadline' => now()->subDay()->toDateString()]);
        $inactive = $this->scholarship(null, [], ['status' => 'inactive']);
        $u->savedScholarships()->attach($s);
        $service = app(RecommendationService::class);
        $rows = $service->forUser($u);
        $this->assertCount(2, $rows);
        $this->assertCount(1, $service->filter($rows, []));
        $this->assertSame([$closed->id], $service->filter($rows, ['status' => 'closed'])->pluck('scholarship.id')->all());
        $this->assertSame([$s->id], $service->filter($rows, ['provider' => 'STEM Demo', 'saved' => 'yes', 'eligibility' => 'Eligible', 'search' => 'Demo'])->pluck('scholarship.id')->all());
        $this->assertCount(0, $service->filter($rows, ['provider' => 'Unknown']));
        $u->profile->academicRecords()->update(['result_value' => 3.0]);
        $this->assertSame('Not Eligible', $service->forUser($u)->firstWhere('scholarship.id', $s->id)['evaluation']->status->value);
        $s->academicRoutes()->update(['minimum' => 3.0]);
        $fresh = $service->forUser($u)->firstWhere('scholarship.id', $s->id)['evaluation'];
        $this->assertSame('Eligible', $fresh->status->value);
        $this->assertSame(0.0, $fresh->academicMargin);
    }
}
