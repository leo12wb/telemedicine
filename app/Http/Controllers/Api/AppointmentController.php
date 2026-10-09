<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Appointment\CancelAppointmentRequest;
use App\Http\Requests\Api\Appointment\FinishAppointmentRequest;
use App\Http\Requests\Api\Appointment\RescheduleAppointmentRequest;
use App\Http\Requests\Api\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Api\Appointment\UpdateNotesRequest;
use App\Http\Resources\Api\AppointmentResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointmentService) {}

    /** GET /api/v1/appointments */
    public function index(Request $request): AnonymousResourceCollection
    {
        $appointments = $this->appointmentService->list(
            $request->user(),
            $request->only('status', 'date', 'from', 'to'),
        );

        return AppointmentResource::collection($appointments);
    }

    /** POST /api/v1/appointments */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $user    = $request->user();
        $patient = $user->isAdmin()
            ? Patient::findOrFail($request->validated()['patient_id'] ?? null)
            : Patient::where('user_id', $user->id)->firstOrFail();

        $appointment = $this->appointmentService->create($request->validated(), $patient);

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/v1/appointments/{appointment} */
    public function show(Request $request, Appointment $appointment): AppointmentResource
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            if ($user->role->value === 'medico') {
                abort_if($appointment->doctor->user_id !== $user->id, 403);
            } else {
                abort_if($appointment->patient->user_id !== $user->id, 403);
            }
        }

        return new AppointmentResource($appointment->load('doctor.user', 'patient.user'));
    }

    /** PATCH /api/v1/appointments/{appointment}/cancel */
    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        $updated = $this->appointmentService->cancel(
            $appointment,
            $request->user(),
            $request->input('reason'),
        );

        return new AppointmentResource($updated);
    }

    /** PATCH /api/v1/appointments/{appointment}/reschedule */
    public function reschedule(RescheduleAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        $patient = Patient::where('user_id', $request->user()->id)->firstOrFail();

        $new = $this->appointmentService->reschedule(
            $appointment,
            $request->validated(),
            $patient,
        );

        return new AppointmentResource($new);
    }

    /** PATCH /api/v1/appointments/{appointment}/start */
    public function start(Request $request, Appointment $appointment): AppointmentResource
    {
        abort_if($appointment->doctor->user_id !== $request->user()->id, 403);

        $updated = $this->appointmentService->start($appointment);

        return new AppointmentResource($updated);
    }

    /** PATCH /api/v1/appointments/{appointment}/finish */
    public function finish(FinishAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        $outcome = AppointmentStatus::from($request->validated()['outcome']);
        $notes   = $request->validated()['notes'] ?? null;

        $updated = $this->appointmentService->finish($appointment, $outcome, $notes);

        return new AppointmentResource($updated);
    }

    /** PATCH /api/v1/appointments/{appointment}/notes */
    public function updateNotes(UpdateNotesRequest $request, Appointment $appointment): AppointmentResource
    {
        $updated = $this->appointmentService->updateNotes($appointment, $request->validated()['notes']);

        return new AppointmentResource($updated);
    }
}
