<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DoctorService
{
    /**
     * Lista médicos paginados.
     * Admin vê todos; demais usuários veem apenas ativos.
     */
    public function list(bool $onlyActive = false, array $filters = []): LengthAwarePaginator
    {
        $query = Doctor::with(['user', 'specialties'])->orderBy('created_at', 'desc');

        if ($onlyActive) {
            $query->active();
        }

        if (! empty($filters['specialty_id'])) {
            $query->whereHas('specialties', fn ($q) => $q->where('specialties.id', $filters['specialty_id']));
        }

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }

        return $query->paginate(15);
    }

    /**
     * Cria usuário + perfil de médico + especialidades em transação única.
     */
    public function create(array $data): Doctor
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => $data['password'],
                'role'      => UserRole::MEDICO,
                'is_active' => true,
            ]);

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'crm'     => $data['crm'],
                'crm_uf'  => strtoupper($data['crm_uf']),
                'phone'   => $data['phone'] ?? null,
                'bio'     => $data['bio'] ?? null,
            ]);

            if (! empty($data['specialty_ids'])) {
                $doctor->specialties()->sync($data['specialty_ids']);
            }

            return $doctor->load('user', 'specialties');
        });
    }

    /**
     * Atualiza dados do médico e opcionalmente suas especialidades.
     */
    public function update(Doctor $doctor, array $data): Doctor
    {
        return DB::transaction(function () use ($doctor, $data) {
            $doctorFields = array_filter(
                array_intersect_key($data, array_flip(['crm', 'crm_uf', 'phone', 'bio', 'photo_path', 'is_active'])),
                fn ($v) => $v !== null,
            );

            if (isset($doctorFields['crm_uf'])) {
                $doctorFields['crm_uf'] = strtoupper($doctorFields['crm_uf']);
            }

            if (! empty($doctorFields)) {
                $doctor->fill($doctorFields)->save();
            }

            if (array_key_exists('specialty_ids', $data)) {
                $doctor->specialties()->sync($data['specialty_ids'] ?? []);
            }

            return $doctor->fresh(['user', 'specialties']);
        });
    }

    public function toggleActive(Doctor $doctor): Doctor
    {
        $doctor->update(['is_active' => ! $doctor->is_active]);

        // Sincroniza o is_active do usuário vinculado
        $doctor->user->update(['is_active' => $doctor->fresh()->is_active]);

        return $doctor->fresh(['user', 'specialties']);
    }

    public function delete(Doctor $doctor): void
    {
        DB::transaction(function () use ($doctor) {
            $doctor->delete();
            $doctor->user->update(['is_active' => false]);
        });
    }
}
