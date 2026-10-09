<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /** Admin lista todos; médicos listam para contexto de consulta. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isDoctor();
    }

    /** Admin, médico ou o próprio paciente visualiza o perfil. */
    public function view(User $user, Patient $patient): bool
    {
        return $user->isAdmin()
            || $user->isDoctor()
            || $user->id === $patient->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Admin ou o próprio paciente atualiza o perfil clínico. */
    public function update(User $user, Patient $patient): bool
    {
        return $user->isAdmin() || $user->id === $patient->user_id;
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->isAdmin();
    }
}
