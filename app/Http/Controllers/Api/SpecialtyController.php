<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Specialty\StoreSpecialtyRequest;
use App\Http\Requests\Api\Specialty\UpdateSpecialtyRequest;
use App\Http\Resources\Api\SpecialtyResource;
use App\Models\Specialty;
use App\Services\SpecialtyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SpecialtyController extends Controller
{
    public function __construct(private readonly SpecialtyService $specialtyService) {}

    /**
     * GET /api/v1/specialties
     * Admin vê todas; demais veem apenas ativas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Specialty::class);

        $onlyActive = ! $request->user()->isAdmin();
        $specialties = $this->specialtyService->list($onlyActive);

        return SpecialtyResource::collection($specialties);
    }

    /**
     * GET /api/v1/specialties/active
     * Lista simplificada (sem paginação) para uso em selects.
     */
    public function active(): AnonymousResourceCollection
    {
        $specialties = $this->specialtyService->listActive();

        return SpecialtyResource::collection($specialties);
    }

    /**
     * POST /api/v1/specialties
     */
    public function store(StoreSpecialtyRequest $request): JsonResponse
    {
        $specialty = $this->specialtyService->create($request->validated());

        return (new SpecialtyResource($specialty))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/specialties/{specialty}
     */
    public function show(Specialty $specialty): SpecialtyResource
    {
        $this->authorize('view', $specialty);

        return new SpecialtyResource($specialty);
    }

    /**
     * PUT /api/v1/specialties/{specialty}
     */
    public function update(UpdateSpecialtyRequest $request, Specialty $specialty): SpecialtyResource
    {
        $updated = $this->specialtyService->update($specialty, $request->validated());

        return new SpecialtyResource($updated);
    }

    /**
     * PATCH /api/v1/specialties/{specialty}/toggle-active
     */
    public function toggleActive(Specialty $specialty): SpecialtyResource
    {
        $this->authorize('toggleActive', Specialty::class);

        $updated = $this->specialtyService->toggleActive($specialty);

        return new SpecialtyResource($updated);
    }

    /**
     * DELETE /api/v1/specialties/{specialty}
     */
    public function destroy(Specialty $specialty): JsonResponse
    {
        $this->authorize('delete', $specialty);

        $this->specialtyService->delete($specialty);

        return response()->json(['message' => 'Especialidade removida com sucesso.']);
    }
}
