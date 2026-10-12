<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $scholarships = Scholarship::all();
        $open = $scholarships->filter(fn ($s) => $s->effectiveStatus() === 'open');

        return view('admin.dashboard', ['stats' => ['total' => $scholarships->count(), 'open' => $open->count(), 'closed' => $scholarships->filter(fn ($s) => $s->effectiveStatus() === 'closed')->count(), 'source_reviews' => $scholarships->filter(fn ($s) => $s->status !== 'inactive' && ($s->source_status !== 'reviewed' || ! $s->last_verified_at || $s->last_verified_at->lt(now()->subDays(90))))->count(), 'students' => User::where('role', 'student')->count()], 'upcoming' => $open->filter(fn ($s) => $s->closingInstant()->lte(now()->addDays(14)))->sortBy('deadline')->take(5), 'recent' => $scholarships->sortByDesc('updated_at')->take(5)]);
    }
}
