<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PatientService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Patient::with('user')->orderBy('created_at', 'desc');

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }

        return $query->paginate(15);
    }

    /**
     * Admin cria usuário + perfil de paciente em transação.
     */
    public function create(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => $data['password'],
                'role'      => UserRole::PACIENTE,
                'is_active' => true,
            ]);

            $patient = Patient::create([
                'user_id'                => $user->id,
                'cpf'                    => $data['cpf'] ?? null,
                'birth_date'             => $data['birth_date'] ?? null,
                'phone'                  => $data['phone'] ?? null,
                'health_insurance'       => $data['health_insurance'] ?? null,
                'health_insurance_number'=> $data['health_insurance_number'] ?? null,
            ]);

            return $patient->load('user');
        });
    }

    /**
     * Retorna ou cria o perfil clínico para um usuário paciente.
     * Usado quando o paciente se auto-cadastrou via /register e ainda não tem perfil.
     */
    public function findOrCreateByUser(User $user): Patient
    {
        return Patient::firstOrCreate(['user_id' => $user->id]);
    }

    public function update(Patient $patient, array $data): Patient
    {
        $patient->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return $patient->fresh('user');
    }

    public function delete(Patient $patient): void
    {
        DB::transaction(function () use ($patient) {
            $patient->delete();
            $patient->user->update(['is_active' => false]);
        });
    }
}
