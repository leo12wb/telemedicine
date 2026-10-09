# Diagrama de Classes de Domínio — Sistema de Telemedicina

**Data:** 2026-10-09
**Formato:** Mermaid
**Status:** Implementado (Fase 5)

> Representa as principais classes de domínio, não todas as classes do framework.

---

```mermaid
classDiagram
    class User {
        +Uuid id
        +string name
        +string email
        +string password
        +UserRole role
        +bool is_active
        +datetime email_verified_at
        +datetime deleted_at
        +doctor() Doctor
        +patient() Patient
        +isAdmin() bool
    }

    class UserRole {
        <<enumeration>>
        ADMIN
        MEDICO
        PACIENTE
    }

    class Doctor {
        +Uuid id
        +Uuid user_id
        +string crm
        +string crm_uf
        +string phone
        +bool is_active
        +datetime deleted_at
        +user() User
        +specialties() Collection
        +schedules() Collection
        +blocks() Collection
        +appointments() Collection
    }

    class Patient {
        +Uuid id
        +Uuid user_id
        +string cpf
        +date birth_date
        +string health_insurance
        +datetime deleted_at
        +user() User
        +appointments() Collection
    }

    class Specialty {
        +Uuid id
        +string name
        +bool is_active
        +doctors() Collection
    }

    class DoctorSchedule {
        +Uuid id
        +Uuid doctor_id
        +int day_of_week
        +time start_time
        +time end_time
        +int slot_duration_minutes
        +bool is_active
        +doctor() Doctor
    }

    class DoctorBlock {
        +Uuid id
        +Uuid doctor_id
        +date block_date
        +time block_start
        +time block_end
        +string reason
        +doctor() Doctor
    }

    class Appointment {
        +Uuid id
        +Uuid doctor_id
        +Uuid patient_id
        +date scheduled_date
        +time scheduled_time
        +int duration_minutes
        +AppointmentStatus status
        +string notes
        +string cancellation_reason
        +Uuid cancelled_by
        +datetime started_at
        +datetime ended_at
        +datetime deleted_at
        +doctor() Doctor
        +patient() Patient
        +scopeActive() Builder
    }

    class AppointmentStatus {
        <<enumeration>>
        AGENDADA
        EM_ANDAMENTO
        CONCLUIDA
        CANCELADA
        PACIENTE_AUSENTE
        +isTerminal() bool
        +label() string
    }

    class AuditLog {
        +Uuid id
        +Uuid user_id
        +string action
        +string entity_type
        +Uuid entity_id
        +array old_values
        +array new_values
        +string ip_address
        +datetime created_at
        +user() User
    }

    %% Services
    class AppointmentService {
        +list(user, filters) Collection
        +create(data, patient) Appointment
        +cancel(appointment, user, reason) Appointment
        +reschedule(appointment, data, patient) Appointment
        +start(appointment) Appointment
        +finish(appointment, outcome, notes) Appointment
        +updateNotes(appointment, notes) Appointment
    }

    class AvailabilityService {
        +getAvailableSlots(doctor, date) array
        +listSchedules(doctor) Collection
        +createSchedule(doctor, data) DoctorSchedule
        +updateSchedule(schedule, data) DoctorSchedule
        +deleteSchedule(schedule) void
        +listBlocks(doctor, filters) Collection
        +createBlock(doctor, data) DoctorBlock
        +deleteBlock(block) void
    }

    class DashboardService {
        +adminDashboard() array
        +doctorDashboard(user) array
        +patientDashboard(user) array
    }

    class AuthService {
        +login(email, password) array
        +logout(user) void
        +register(data) User
    }

    %% Relacionamentos de modelo
    User "1" --> "0..1" Doctor : tem perfil
    User "1" --> "0..1" Patient : tem perfil
    User "1" *-- "1" UserRole : possui
    Doctor "1" --> "*" Specialty : atua em
    Doctor "1" --> "*" DoctorSchedule : configura
    Doctor "1" --> "*" DoctorBlock : bloqueia
    Doctor "1" --> "*" Appointment : realiza
    Patient "1" --> "*" Appointment : agenda
    Appointment "1" *-- "1" AppointmentStatus : tem
    User "1" --> "*" AuditLog : gera

    %% Dependências de serviço
    AppointmentService ..> Appointment : cria/atualiza
    AppointmentService ..> AvailabilityService : verifica slots
    DashboardService ..> Appointment : consulta
    DashboardService ..> Doctor : consulta
    DashboardService ..> Patient : consulta
    AuthService ..> User : autentica
```
