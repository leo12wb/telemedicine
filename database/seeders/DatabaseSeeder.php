<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
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
        Patient::factory()->create(['user_id' => $devPatientUser->id]);
    }
}
