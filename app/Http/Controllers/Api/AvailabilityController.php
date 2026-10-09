<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Availability\StoreBlockRequest;
use App\Http\Requests\Api\Availability\StoreScheduleRequest;
use App\Http\Requests\Api\Availability\UpdateScheduleRequest;
use App\Http\Resources\Api\DoctorBlockResource;
use App\Http\Resources\Api\DoctorScheduleResource;
use App\Models\Doctor;
use App\Models\DoctorBlock;
use App\Models\DoctorSchedule;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availabilityService) {}

    // ─── Schedules ────────────────────────────────────────────────────────────

    /** GET /api/v1/doctors/{doctor}/schedules */
    public function indexSchedules(Doctor $doctor): AnonymousResourceCollection
    {
        $schedules = $this->availabilityService->listSchedules($doctor);

        return DoctorScheduleResource::collection($schedules);
    }

    /** POST /api/v1/doctors/{doctor}/schedules */
    public function storeSchedule(StoreScheduleRequest $request, Doctor $doctor): JsonResponse
    {
        $schedule = $this->availabilityService->createSchedule($doctor, $request->validated());

        return (new DoctorScheduleResource($schedule))
            ->response()
            ->setStatusCode(201);
    }

    /** PUT /api/v1/doctors/{doctor}/schedules/{schedule} */
    public function updateSchedule(UpdateScheduleRequest $request, Doctor $doctor, DoctorSchedule $schedule): DoctorScheduleResource
    {
        abort_if($schedule->doctor_id !== $doctor->id, 404);

        $updated = $this->availabilityService->updateSchedule($schedule, $request->validated());

        return new DoctorScheduleResource($updated);
    }

    /** DELETE /api/v1/doctors/{doctor}/schedules/{schedule} */
    public function destroySchedule(Request $request, Doctor $doctor, DoctorSchedule $schedule): JsonResponse
    {
        abort_if($schedule->doctor_id !== $doctor->id, 404);

        $user = $request->user();
        abort_if(! $user->isAdmin() && $doctor->user_id !== $user->id, 403);

        $this->availabilityService->deleteSchedule($schedule);

        return response()->json(['message' => 'Horário removido com sucesso.']);
    }

    // ─── Blocks ───────────────────────────────────────────────────────────────

    /** GET /api/v1/doctors/{doctor}/blocks */
    public function indexBlocks(Request $request, Doctor $doctor): AnonymousResourceCollection
    {
        $blocks = $this->availabilityService->listBlocks(
            $doctor,
            $request->query('from'),
            $request->query('to'),
        );

        return DoctorBlockResource::collection($blocks);
    }

    /** POST /api/v1/doctors/{doctor}/blocks */
    public function storeBlock(StoreBlockRequest $request, Doctor $doctor): JsonResponse
    {
        $block = $this->availabilityService->createBlock($doctor, $request->validated());

        return (new DoctorBlockResource($block))
            ->response()
            ->setStatusCode(201);
    }

    /** DELETE /api/v1/doctors/{doctor}/blocks/{block} */
    public function destroyBlock(Request $request, Doctor $doctor, DoctorBlock $block): JsonResponse
    {
        abort_if($block->doctor_id !== $doctor->id, 404);

        $user = $request->user();
        abort_if(! $user->isAdmin() && $doctor->user_id !== $user->id, 403);

        $this->availabilityService->deleteBlock($block);

        return response()->json(['message' => 'Bloqueio removido com sucesso.']);
    }

    // ─── Availability (slots) ─────────────────────────────────────────────────

    /** GET /api/v1/doctors/{doctor}/availability?date=YYYY-MM-DD */
    public function availability(Request $request, Doctor $doctor): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        $slots = $this->availabilityService->getAvailableSlots($doctor, $request->query('date'));

        return response()->json(['data' => $slots]);
    }
}
