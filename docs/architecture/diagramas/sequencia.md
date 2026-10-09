# Diagramas de Sequência — Sistema de Telemedicina

**Data:** 2026-10-09
**Formato:** Mermaid
**Status:** Proposta inicial — aguardando aprovação

---

## SEQ-01 — Autenticação (Login)

```mermaid
sequenceDiagram
    actor P as Usuário (qualquer perfil)
    participant FE as Vue 3 (Frontend)
    participant CTRL as AuthController
    participant SVC as AuthService
    participant MODEL as User (Model)
    participant DB as PostgreSQL
    participant SANCTUM as Sanctum

    P->>FE: Preenche e-mail e senha
    FE->>CTRL: POST /api/login {email, password}
    CTRL->>CTRL: Valida via LoginRequest
    CTRL->>SVC: login(email, password)
    SVC->>MODEL: findByEmail(email)
    MODEL->>DB: SELECT * FROM users WHERE email = ?
    DB-->>MODEL: user record
    MODEL-->>SVC: User
    SVC->>SVC: Hash::check(password, user.password)
    alt Credenciais inválidas
        SVC-->>CTRL: throw AuthenticationException
        CTRL-->>FE: 401 {message: "Credenciais inválidas"}
        FE-->>P: Exibe mensagem de erro
    else Credenciais válidas
        SVC->>SANCTUM: createToken(user)
        SANCTUM->>DB: INSERT INTO personal_access_tokens
        SANCTUM-->>SVC: token string
        SVC->>DB: INSERT INTO audit_logs (login)
        SVC-->>CTRL: {user, token}
        CTRL-->>FE: 200 {user: UserResource, token: "..."}
        FE->>FE: Armazena token (localStorage/store)
        FE-->>P: Redireciona para dashboard do perfil
    end
```

---

## SEQ-02 — Agendamento de Consulta

```mermaid
sequenceDiagram
    actor P as Paciente
    participant FE as Vue 3 (Frontend)
    participant CTRL as AppointmentController
    participant REQ as StoreAppointmentRequest
    participant SVC as AppointmentService
    participant AVAIL as AvailabilityService
    participant REPO as AppointmentRepository
    participant MODEL as Appointment (Model)
    participant DB as PostgreSQL
    participant JOB as NotificationJob

    P->>FE: Seleciona médico, especialidade, data e hora
    FE->>CTRL: POST /api/appointments {doctor_id, specialty_id, scheduled_at}
    CTRL->>REQ: Valida campos obrigatórios e formatos
    REQ-->>CTRL: Dados validados
    CTRL->>CTRL: Authorize via AppointmentPolicy
    CTRL->>SVC: schedule(patient_id, data)
    
    SVC->>AVAIL: isSlotAvailable(doctor_id, scheduled_at)
    AVAIL->>DB: SELECT slot disponível + sem bloqueio
    DB-->>AVAIL: resultado
    
    alt Slot indisponível
        AVAIL-->>SVC: false
        SVC-->>CTRL: throw SlotUnavailableException
        CTRL-->>FE: 409 {message: "Horário indisponível"}
        FE-->>P: Exibe erro
    else Slot disponível
        SVC->>DB: BEGIN TRANSACTION
        SVC->>REPO: create(appointment_data) com lockForUpdate
        REPO->>DB: SELECT FOR UPDATE + INSERT INTO appointments
        DB-->>REPO: appointment record
        REPO-->>SVC: Appointment
        SVC->>DB: INSERT INTO audit_logs
        SVC->>DB: COMMIT
        SVC->>JOB: dispatch(AppointmentConfirmedJob)
        JOB->>DB: INSERT INTO jobs (queue)
        SVC-->>CTRL: Appointment
        CTRL-->>FE: 201 {appointment: AppointmentResource}
        FE-->>P: Confirmação do agendamento
    end

    Note over JOB,DB: Processado assincronamente pelo queue worker
    JOB->>JOB: Enviar e-mail ao paciente e ao médico
```

---

## SEQ-03 — Cancelamento de Consulta (pelo Paciente)

```mermaid
sequenceDiagram
    actor P as Paciente
    participant FE as Vue 3 (Frontend)
    participant CTRL as AppointmentController
    participant SVC as AppointmentService
    participant REPO as AppointmentRepository
    participant DB as PostgreSQL
    participant JOB as NotificationJob

    P->>FE: Solicita cancelamento de consulta
    FE->>CTRL: PATCH /api/appointments/{id}/cancel {reason}
    CTRL->>CTRL: Authorize via AppointmentPolicy (própria consulta)
    CTRL->>SVC: cancel(appointment_id, user_id, reason)
    SVC->>REPO: findById(appointment_id)
    REPO->>DB: SELECT * FROM appointments WHERE id = ?
    DB-->>REPO: appointment record
    REPO-->>SVC: Appointment

    alt Status não permite cancelamento
        SVC-->>CTRL: throw AppointmentCannotBeCancelledException
        CTRL-->>FE: 422 {message: "Consulta não pode ser cancelada"}
    else Fora do prazo (paciente)
        SVC-->>CTRL: throw CancellationDeadlineException
        CTRL-->>FE: 422 {message: "Prazo de cancelamento expirado"}
    else Cancelamento permitido
        SVC->>DB: BEGIN TRANSACTION
        SVC->>REPO: updateStatus(id, 'cancelada', reason, cancelled_by)
        REPO->>DB: UPDATE appointments SET status = 'cancelada'
        SVC->>DB: INSERT INTO audit_logs
        SVC->>DB: COMMIT
        SVC->>JOB: dispatch(AppointmentCancelledJob)
        SVC-->>CTRL: Appointment atualizado
        CTRL-->>FE: 200 {appointment: AppointmentResource}
        FE-->>P: Confirmação do cancelamento
    end
```

---

## SEQ-04 — Início e Encerramento de Consulta (Médico)

```mermaid
sequenceDiagram
    actor M as Médico
    participant FE as Vue 3 (Frontend)
    participant CTRL as ConsultationController
    participant SVC as ConsultationService
    participant REPO as ConsultationRepository
    participant DB as PostgreSQL

    %% Início da consulta
    M->>FE: Clica em "Iniciar Consulta"
    FE->>CTRL: PATCH /api/consultations/{id}/start
    CTRL->>CTRL: Authorize via ConsultationPolicy (médico da consulta)
    CTRL->>SVC: start(appointment_id, doctor_id)
    SVC->>SVC: Valida janela de tolerância horária (RN-016)
    SVC->>REPO: updateStatus(id, 'em_andamento', started_at: now())
    REPO->>DB: UPDATE appointments SET status = 'em_andamento', started_at = NOW()
    DB-->>REPO: OK
    REPO-->>SVC: Appointment
    SVC->>DB: INSERT INTO audit_logs
    SVC-->>CTRL: Appointment
    CTRL-->>FE: 200 {appointment: AppointmentResource}
    FE-->>M: Interface de atendimento ativa

    %% Registro de anotações
    M->>FE: Registra anotações clínicas
    FE->>CTRL: POST /api/consultations/{id}/notes {subjective, objective, assessment, plan}
    CTRL->>SVC: saveNotes(appointment_id, notes_data)
    SVC->>DB: INSERT/UPDATE clinical_notes
    DB-->>SVC: OK
    SVC-->>CTRL: ClinicalNote
    CTRL-->>FE: 201 {note: ClinicalNoteResource}

    %% Encerramento
    M->>FE: Clica em "Encerrar Consulta"
    FE->>CTRL: PATCH /api/consultations/{id}/end {outcome: "concluida"}
    CTRL->>SVC: end(appointment_id, outcome, doctor_id)
    SVC->>REPO: updateStatus(id, outcome, ended_at: now())
    REPO->>DB: UPDATE appointments SET status = ?, ended_at = NOW()
    DB-->>REPO: OK
    SVC->>DB: INSERT INTO audit_logs
    SVC-->>CTRL: Appointment
    CTRL-->>FE: 200 {appointment: AppointmentResource}
    FE-->>M: Consulta encerrada
```
