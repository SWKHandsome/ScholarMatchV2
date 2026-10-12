<?php

namespace App\Services;

use App\Enums\EligibilityStatus as E;
use App\Models\IncomeCategory;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RecommendationService
{
    public function __construct(private ScholarshipEligibilityService $eligibility) {}

    public function forUser(User $user, bool $savedOnly = false): Collection
    {
        $profile = $user->profile()->with(['academicRecords', 'studyField'])->first();
        $saved = $user->savedScholarships()->get()->keyBy('id');
        $query = Scholarship::withEvaluationData();
        if ($savedOnly) {
            $query->whereIn('id', $saved->keys());
        } else {
            $query->where('status', '!=', 'inactive');
        }
        $categories = IncomeCategory::all();

        return $query->get()->map(fn ($s) => ['scholarship' => $s, 'evaluation' => $this->eligibility->evaluate($profile, $s, $categories), 'saved' => $saved->has($s->id), 'saved_at' => $saved->get($s->id)?->pivot->created_at]);
    }

    public function filter(Collection $rows, array $filters): Collection
    {
        return $rows->filter(function ($row) use ($filters) {
            $s = $row['scholarship'];
            $rules = $s->requirements?->criteria ?? [];
            $status = $filters['status'] ?? 'open';
            if ($status !== 'all' && $s->effectiveStatus() !== $status) {
                return false;
            }
            if (! empty($filters['eligibility']) && $row['evaluation']->status->value !== $filters['eligibility']) {
                return false;
            }
            if (! empty($filters['provider']) && $s->provider !== $filters['provider']) {
                return false;
            }
            if (! empty($filters['search']) && stripos($s->name.' '.$s->provider, $filters['search']) === false) {
                return false;
            }
            if (($filters['saved'] ?? '') === 'yes' && ! $row['saved'] || ($filters['saved'] ?? '') === 'no' && $row['saved']) {
                return false;
            }
            foreach (['funding_level', 'institution_type'] as $key) {
                if (! empty($filters[$key]) && ($rules[$key]['mode'] ?? '') !== 'none' && ! in_array($filters[$key], $rules[$key]['values'] ?? [], true)) {
                    return false;
                }
            }
            if (! empty($filters['field']) && ($rules['study_field']['mode'] ?? '') !== 'none' && ! $s->studyFields->contains('id', (int) $filters['field'])) {
                return false;
            }

            return true;
        })->values();
    }

    private function group(array $row, string $sort): int
    {
        return match ($row['evaluation']->status) {
            E::Eligible => ($sort === 'academic' && $row['evaluation']->academicMargin === null) ? 1 : 0,E::Manual => 2,E::NotEligible => 3
        };
    }

    public function order(Collection $rows, string $sort = 'academic'): Collection
    {
        return $rows->sort(function ($a, $b) use ($sort) {
            $group = $this->group($a, $sort) <=> $this->group($b, $sort);
            if ($group !== 0) {
                return $group;
            }
            $primary = match ($sort) {
                'name' => strcasecmp($a['scholarship']->name, $b['scholarship']->name),
                'recent' => $b['scholarship']->created_at->getTimestamp() <=> $a['scholarship']->created_at->getTimestamp(),
                'deadline' => $a['scholarship']->closingInstant()->getTimestamp() <=> $b['scholarship']->closingInstant()->getTimestamp(),
                default => ($b['evaluation']->academicMargin ?? 0) <=> ($a['evaluation']->academicMargin ?? 0),
            };

            return $primary ?: ($a['scholarship']->closingInstant()->getTimestamp() <=> $b['scholarship']->closingInstant()->getTimestamp()) ?: ($a['scholarship']->id <=> $b['scholarship']->id);
        })->values();
    }

    public function paginate(Collection $rows, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        return new LengthAwarePaginator($rows->slice(($page - 1) * $perPage, $perPage)->values(), $rows->count(), $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]);
    }
}
