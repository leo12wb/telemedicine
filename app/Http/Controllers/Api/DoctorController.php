<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Doctor\StoreDoctorRequest;
use App\Http\Requests\Api\Doctor\UpdateDoctorRequest;
use App\Http\Resources\Api\DoctorResource;
use App\Models\Doctor;
use App\Services\DoctorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    public function __construct(private readonly DoctorService $doctorService) {}

    /** GET /api/v1/doctors */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Doctor::class);

        $onlyActive = ! $request->user()->isAdmin();
        $doctors    = $this->doctorService->list($onlyActive, $request->only('specialty_id', 'search'));

        return DoctorResource::collection($doctors);
    }

    /** POST /api/v1/doctors */
    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $doctor = $this->doctorService->create($request->validated());

        return (new DoctorResource($doctor))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/v1/doctors/{doctor} */
    public function show(Doctor $doctor): DoctorResource
    {
        $this->authorize('view', $doctor);

        return new DoctorResource($doctor->load('user', 'specialties'));
    }

    /** PUT /api/v1/doctors/{doctor} */
    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        $updated = $this->doctorService->update($doctor, $request->validated());

        return new DoctorResource($updated);
    }

    /** PATCH /api/v1/doctors/{doctor}/toggle-active */
    public function toggleActive(Doctor $doctor): DoctorResource
    {
        $this->authorize('toggleActive', Doctor::class);

        $updated = $this->doctorService->toggleActive($doctor);

        return new DoctorResource($updated);
    }

    /** DELETE /api/v1/doctors/{doctor} */
    public function destroy(Doctor $doctor): JsonResponse
    {
        $this->authorize('delete', $doctor);

        $this->doctorService->delete($doctor);

        return response()->json(['message' => 'Médico removido com sucesso.']);
    }
}
