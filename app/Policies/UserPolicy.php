<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Admin lista todos; demais não têm acesso à listagem completa. */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    /** Admin vê qualquer perfil; usuário comum vê apenas o próprio. */
    public function view(User $authUser, User $user): bool
    {
        return $authUser->isAdmin() || $authUser->id === $user->id;
    }

    /** Somente admin cria usuários. */
    public function create(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    /** Admin atualiza qualquer usuário; usuário comum atualiza apenas o próprio perfil. */
    public function update(User $authUser, User $user): bool
    {
        return $authUser->isAdmin() || $authUser->id === $user->id;
    }

    /** Somente admin ativa/desativa. */
    public function toggleActive(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    /** Somente admin exclui. Admin não pode se auto-excluir. */
    public function delete(User $authUser, User $user): bool
    {
        return $authUser->isAdmin() && $authUser->id !== $user->id;
    }
}
