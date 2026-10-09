<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Patient\StorePatientRequest;
use App\Http\Requests\Api\Patient\UpdatePatientRequest;
use App\Http\Resources\Api\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientController extends Controller
{
    public function __construct(private readonly PatientService $patientService) {}

    /** GET /api/v1/patients */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Patient::class);

        $patients = $this->patientService->list($request->only('search'));

        return PatientResource::collection($patients);
    }

    /** POST /api/v1/patients */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patientService->create($request->validated());

        return (new PatientResource($patient))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/v1/patients/{patient} */
    public function show(Patient $patient): PatientResource
    {
        $this->authorize('view', $patient);

        return new PatientResource($patient->load('user'));
    }

    /**
     * GET /api/v1/patients/profile
     * Retorna (ou cria) o perfil clínico do paciente autenticado.
     */
    public function profile(Request $request): JsonResponse
    {
        $patient = $this->patientService->findOrCreateByUser($request->user());

        // Força 200 mesmo quando o perfil é criado agora (GET não deve retornar 201)
        return (new PatientResource($patient->load('user')))
            ->response()
            ->setStatusCode(200);
    }

    /** PUT /api/v1/patients/{patient} */
    public function update(UpdatePatientRequest $request, Patient $patient): PatientResource
    {
        $updated = $this->patientService->update($patient, $request->validated());

        return new PatientResource($updated);
    }

    /** DELETE /api/v1/patients/{patient} */
    public function destroy(Patient $patient): JsonResponse
    {
        $this->authorize('delete', $patient);

        $this->patientService->delete($patient);

        return response()->json(['message' => 'Paciente removido com sucesso.']);
    }
}
