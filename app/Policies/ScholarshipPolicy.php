<?php

namespace App\Policies;

use App\Models\Scholarship;
use App\Models\User;

class ScholarshipPolicy
{
    public function view(User $user, Scholarship $s): bool
    {
        return $user->is_active && ($user->role === 'admin' || $s->status !== 'inactive' || $user->savedScholarships()->where('scholarship_id', $s->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->role === 'admin';
    }

    public function update(User $user, Scholarship $s): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Scholarship $s): bool
    {
        return $this->create($user);
    }
}
