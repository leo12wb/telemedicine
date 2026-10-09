<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\SystemSetting;
use App\Models\User;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin ───────────────────────────────────────────────────────────
        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@telemedicina.local',
            'password' => bcrypt('password'),
        ]);

        // ─── Especialidades ──────────────────────────────────────────────────
        $specialties = collect([
            'Clínica Geral',
            'Cardiologia',
            'Dermatologia',
            'Ortopedia',
            'Psiquiatria',
        ])->map(fn (string $name) => Specialty::create(['name' => $name]));

        // ─── Médicos ─────────────────────────────────────────────────────────
        $doctorUsers = User::factory()->medico()->count(3)->create();

        $doctorUsers->each(function (User $user, int $i) use ($specialties) {
            $doctor = Doctor::factory()->forUser($user)->create();
            $doctor->specialties()->attach($specialties->slice($i, 2)->pluck('id'));
        });

        // Médico de fácil acesso para desenvolvimento
        $devDoctorUser = User::factory()->medico()->create([
            'name' => 'Dr. Dev',
            'email' => 'medico@telemedicina.local',
            'password' => bcrypt('password'),
        ]);
        Doctor::factory()->forUser($devDoctorUser)->create([
            'crm' => '99999',
            'crm_uf' => 'SP',
        ])->specialties()->attach($specialties->first()->id);

        // ─── Pacientes ───────────────────────────────────────────────────────
        User::factory()->paciente()->count(5)->create()
            ->each(fn (User $user) => Patient::factory()->create(['user_id' => $user->id]));

        // Paciente de fácil acesso para desenvolvimento
        $devPatientUser = User::factory()->paciente()->create([
            'name' => 'Paciente Dev',
            'email' => 'paciente@telemedicina.local',
            'password' => bcrypt('password'),
        ]);
        $devPatient = Patient::factory()->create(['user_id' => $devPatientUser->id]);

        // ─── Consulta de demonstração ─────────────────────────────────────────
        $devDoctor = Doctor::where('user_id', $devDoctorUser->id)->first();

        Appointment::create([
            'doctor_id'        => $devDoctor->id,
            'patient_id'       => $devPatient->id,
            'scheduled_date'   => now()->toDateString(),
            'scheduled_time'   => '17:00:00',
            'duration_minutes' => 30,
            'status'           => AppointmentStatus::AGENDADA,
        ]);

        // ─── Configurações do sistema ─────────────────────────────────────────
        SystemSetting::setMany([
            'video_provider'   => 'jitsi',
            'jitsi_server_url' => 'https://meet.jit.si',
        ]);
    }
}
