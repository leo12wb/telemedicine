<?php

namespace App\Services;

use App\Models\Specialty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SpecialtyService
{
    /**
     * Lista especialidades. Admin vê todas; demais usuários veem apenas ativas.
     */
    public function list(bool $onlyActive = false): LengthAwarePaginator
    {
        $query = Specialty::query()->orderBy('name');

        if ($onlyActive) {
            $query->active();
        }

        return $query->paginate(50);
    }

    /**
     * Retorna todas as especialidades ativas (sem paginação, para selects).
     */
    public function listActive(): Collection
    {
        return Specialty::active()->orderBy('name')->get();
    }

    public function create(array $data): Specialty
    {
        return Specialty::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Specialty $specialty, array $data): Specialty
    {
        $specialty->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return $specialty->fresh();
    }

    public function toggleActive(Specialty $specialty): Specialty
    {
        $specialty->update(['is_active' => ! $specialty->is_active]);

        return $specialty->fresh();
    }

    public function delete(Specialty $specialty): void
    {
        $specialty->delete();
    }
}
