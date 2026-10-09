<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Doctor\UploadDoctorPhotoRequest;
use App\Http\Resources\Api\DoctorResource;
use App\Models\Doctor;
use App\Services\DoctorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorPhotoController extends Controller
{
    public function __construct(private readonly DoctorService $doctorService) {}

    /** POST /api/v1/doctors/{doctor}/photo */
    public function store(UploadDoctorPhotoRequest $request, Doctor $doctor): DoctorResource
    {
        $updated = $this->doctorService->uploadPhoto($doctor, $request->file('photo'));

        return new DoctorResource($updated->load('user', 'specialties'));
    }

    /** DELETE /api/v1/doctors/{doctor}/photo */
    public function destroy(Request $request, Doctor $doctor): JsonResponse
    {
        $this->authorize('update', $doctor);

        $this->doctorService->deletePhoto($doctor);

        return response()->json(['message' => 'Foto removida com sucesso.']);
    }
}
