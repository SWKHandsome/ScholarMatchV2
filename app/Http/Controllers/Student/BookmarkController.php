<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BookmarkController extends Controller
{
    public function store(Request $request, Scholarship $scholarship): RedirectResponse
    {
        Gate::authorize('view', $scholarship);
        DB::table('saved_scholarships')->insertOrIgnore(['user_id' => $request->user()->id, 'scholarship_id' => $scholarship->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Scholarship saved.');
    }

    public function destroy(Request $request, Scholarship $scholarship): RedirectResponse
    {
        DB::table('saved_scholarships')->where('user_id', $request->user()->id)->where('scholarship_id', $scholarship->id)->delete();

        return back()->with('success', 'Scholarship removed from your saved list.');
    }
}
