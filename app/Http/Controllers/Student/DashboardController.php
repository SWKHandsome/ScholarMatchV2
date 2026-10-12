<?php

namespace App\Http\Controllers\Student;

use App\Enums\EligibilityStatus as E;
use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, RecommendationService $service): View
    {
        if ($request->user()->role === 'admin') {
            return app(\App\Http\Controllers\Admin\DashboardController::class)();
        }
        $rows = $service->order($service->filter($service->forUser($request->user()), []));
        $eligible = $rows->filter(fn ($r) => $r['evaluation']->status === E::Eligible);
        $upcoming = $rows->filter(fn ($r) => $r['scholarship']->closingInstant()->lessThanOrEqualTo(now()->addDays(14)))->sortBy(fn ($r) => $r['scholarship']->closingInstant()->timestamp);

        return view('dashboard', ['profile' => $request->user()->profile ?? new StudentProfile, 'leading' => $eligible->take(3), 'upcoming' => $upcoming->take(5), 'stats' => ['eligible' => $eligible->count(), 'manual' => $rows->filter(fn ($r) => $r['evaluation']->status === E::Manual)->count(), 'saved' => $request->user()->savedScholarships()->count(), 'upcoming' => $upcoming->count()]]);
    }
}
