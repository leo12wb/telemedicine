<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Lista usuários paginados com filtros opcionais.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->orderBy('name');

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        return $query->paginate(15);
    }

    /**
     * Cria um novo usuário (pelo admin).
     */
    public function create(array $data): User
    {
        return User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'role'      => UserRole::from($data['role']),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Atualiza dados de um usuário.
     * Campos role e is_active são opcionais (só admin pode enviá-los).
     */
    public function update(User $user, array $data): User
    {
        $payload = array_filter([
            'name'      => $data['name'] ?? null,
            'email'     => $data['email'] ?? null,
            'password'  => isset($data['password']) ? Hash::make($data['password']) : null,
            'role'      => isset($data['role']) ? UserRole::from($data['role']) : null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null);

        $user->fill($payload)->save();

        return $user->fresh();
    }

    /**
     * Alterna o status ativo/inativo do usuário.
     */
    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);

        return $user->fresh();
    }

    /**
     * Remove o usuário (soft delete).
     */
    public function delete(User $user): void
    {
        $user->delete();
    }
}
