<?php

namespace App\Policies;

use App\Models\Specialty;
use App\Models\User;

class SpecialtyPolicy
{
    /** Todos os usuários autenticados podem listar especialidades. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Todos os usuários autenticados podem visualizar uma especialidade. */
    public function view(User $user, Specialty $specialty): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Specialty $specialty): bool
    {
        return $user->isAdmin();
    }

    public function toggleActive(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Specialty $specialty): bool
    {
        return $user->isAdmin();
    }
}
