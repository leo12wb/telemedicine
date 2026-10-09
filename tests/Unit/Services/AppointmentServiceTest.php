<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelled;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

describe('AppointmentService::create', function () {

    it('agenda consulta e cria registro no banco', function () {
        Notification::fake();

        $doctor  = Doctor::factory()->create();
        $user    = User::factory()->paciente()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);

        $date = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $appt = app(AppointmentService::class)->create([
            'doctor_id'      => $doctor->id,
            'scheduled_date' => $date,
            'scheduled_time' => '10:00',
        ], $patient);

        expect($appt->status)->toBe(AppointmentStatus::AGENDADA)
            ->and($appt->patient_id)->toBe($patient->id)
            ->and($appt->doctor_id)->toBe($doctor->id);

        Notification::assertSentTo($user, AppointmentBooked::class);
    });

    it('lança ValidationException em caso de double-booking', function () {
        Notification::fake();

        $doctor  = Doctor::factory()->create();
        $user1   = User::factory()->paciente()->create();
        $patient1 = Patient::factory()->create(['user_id' => $user1->id]);
        $date    = Carbon::now()->addDays(5)->format('Y-m-d');

        // Agenda primeira consulta
        Appointment::factory()->forDoctor($doctor)->forPatient($patient1)->create([
            'scheduled_date' => $date,
            'scheduled_time' => '10:00:00',
            'status'         => AppointmentStatus::AGENDADA,
        ]);

        $user2    = User::factory()->paciente()->create();
        $patient2 = Patient::factory()->create(['user_id' => $user2->id]);

        expect(fn () => app(AppointmentService::class)->create([
            'doctor_id'      => $doctor->id,
            'scheduled_date' => $date,
            'scheduled_time' => '10:00',
        ], $patient2))->toThrow(\Illuminate\Validation\ValidationException::class);
    });
});

describe('AppointmentService::cancel', function () {

    it('admin cancela consulta em qualquer prazo', function () {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $appt  = Appointment::factory()->scheduled()->create([
            'scheduled_date' => Carbon::now()->addHours(1)->format('Y-m-d'),
        ]);

        $cancelled = app(AppointmentService::class)->cancel($appt, $admin, 'Motivo administrativo');

        expect($cancelled->status)->toBe(AppointmentStatus::CANCELADA)
            ->and($cancelled->cancellation_reason)->toBe('Motivo administrativo');
    });

    it('não permite cancelar consulta já encerrada', function () {
        $admin = User::factory()->admin()->create();
        $appt  = Appointment::factory()->concluded()->create();

        expect(fn () => app(AppointmentService::class)->cancel($appt, $admin))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });
});

describe('AppointmentService::start', function () {

    it('transiciona de agendada para em_andamento', function () {
        $appt = Appointment::factory()->scheduled()->create();

        $started = app(AppointmentService::class)->start($appt);

        expect($started->status)->toBe(AppointmentStatus::EM_ANDAMENTO)
            ->and($started->started_at)->not->toBeNull();
    });

    it('lança erro ao tentar iniciar consulta não agendada', function () {
        $appt = Appointment::factory()->inProgress()->create();

        expect(fn () => app(AppointmentService::class)->start($appt))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });
});

describe('AppointmentService::finish', function () {

    it('encerra como concluída e registra ended_at', function () {
        $appt = Appointment::factory()->inProgress()->create();

        $finished = app(AppointmentService::class)->finish(
            $appt,
            AppointmentStatus::CONCLUIDA,
            'Pressão normal.'
        );

        expect($finished->status)->toBe(AppointmentStatus::CONCLUIDA)
            ->and($finished->ended_at)->not->toBeNull()
            ->and($finished->notes)->toBe('Pressão normal.');
    });

    it('encerra como paciente ausente', function () {
        $appt = Appointment::factory()->inProgress()->create();

        $finished = app(AppointmentService::class)->finish($appt, AppointmentStatus::PACIENTE_AUSENTE);

        expect($finished->status)->toBe(AppointmentStatus::PACIENTE_AUSENTE);
    });
});

describe('AppointmentService::updateNotes', function () {

    it('atualiza notas em consulta em andamento', function () {
        $appt = Appointment::factory()->inProgress()->create();

        $updated = app(AppointmentService::class)->updateNotes($appt, 'Exame normal.');

        expect($updated->notes)->toBe('Exame normal.');
    });

    it('não permite editar notas após encerramento', function () {
        $appt = Appointment::factory()->concluded()->create();

        expect(fn () => app(AppointmentService::class)->updateNotes($appt, 'Tentativa'))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });
});
