<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Doctor $doctor): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Admin atualiza qualquer médico; o próprio médico atualiza seu perfil. */
    public function update(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin() || $user->id === $doctor->user_id;
    }

    public function toggleActive(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin();
    }
}
