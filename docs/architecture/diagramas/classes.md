# Diagrama de Classes de Domínio — Sistema de Telemedicina

**Data:** 2026-10-09
**Formato:** Mermaid
**Status:** Proposta inicial — aguardando aprovação

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
        +hasRole(role) bool
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
        +user() User
        +specialties() Collection
        +schedules() Collection
        +appointments() Collection
    }

    class Patient {
        +Uuid id
        +Uuid user_id
        +string cpf
        +date birth_date
        +string health_insurance
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
        +generateSlots(date) Collection
    }

    class DoctorBlock {
        +Uuid id
        +Uuid doctor_id
        +date block_date
        +time block_start
        +time block_end
        +string reason
        +doctor() Doctor
        +isFullDay() bool
    }

    class Appointment {
        +Uuid id
        +Uuid doctor_id
        +Uuid patient_id
        +Uuid specialty_id
        +datetime scheduled_at
        +int duration_minutes
        +AppointmentStatus status
        +string cancellation_reason
        +datetime started_at
        +datetime ended_at
        +doctor() Doctor
        +patient() Patient
        +specialty() Specialty
        +clinicalNote() ClinicalNote
        +canBeCancelled() bool
        +canBeStarted() bool
    }

    class AppointmentStatus {
        <<enumeration>>
        AGENDADA
        EM_ANDAMENTO
        CONCLUIDA
        CANCELADA
        PACIENTE_AUSENTE
    }

    class ClinicalNote {
        +Uuid id
        +Uuid appointment_id
        +string subjective
        +string objective
        +string assessment
        +string plan
        +string notes
        +appointment() Appointment
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
        +schedule(patient, data) Appointment
        +cancel(appointment, user, reason) Appointment
        +reschedule(appointment, new_slot) Appointment
    }

    class AvailabilityService {
        +isSlotAvailable(doctor_id, datetime) bool
        +getAvailableSlots(doctor_id, date) Collection
    }

    class ConsultationService {
        +start(appointment, doctor) Appointment
        +end(appointment, outcome) Appointment
        +saveNotes(appointment, data) ClinicalNote
    }

    class AuthService {
        +login(email, password) array
        +logout(user) void
        +register(data) User
    }

    %% Relacionamentos
    User "1" --> "0..1" Doctor : tem perfil
    User "1" --> "0..1" Patient : tem perfil
    User "1" *-- "1" UserRole : possui
    Doctor "1" --> "*" Specialty : atua em
    Doctor "1" --> "*" DoctorSchedule : configura
    Doctor "1" --> "*" DoctorBlock : bloqueia
    Doctor "1" --> "*" Appointment : realiza
    Patient "1" --> "*" Appointment : agenda
    Specialty "1" --> "*" Appointment : categoriza
    Appointment "1" --> "0..1" ClinicalNote : gera
    Appointment "1" *-- "1" AppointmentStatus : tem
    User "1" --> "*" AuditLog : gera

    AppointmentService ..> Appointment : cria/atualiza
    AppointmentService ..> AvailabilityService : usa
    ConsultationService ..> Appointment : atualiza
    ConsultationService ..> ClinicalNote : cria
    AuthService ..> User : autentica
```
