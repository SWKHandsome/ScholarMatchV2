<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', ['users' => User::with('profile')->orderBy('name')->get()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        if ($user->id === $request->user()->id && ! $request->boolean('is_active')) {
            throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own administrator account.']);
        }
        $user->update($data);

        return back()->with('success', 'Account status updated.');
    }
}
