# Diagrama de Componentes — Sistema de Telemedicina

**Data:** 2026-10-09
**Formato:** Mermaid
**Status:** Proposta inicial — aguardando aprovação

---

## Visão de alto nível (C4 — Nível 2: Containers)

```mermaid
graph TB
    subgraph Browser["Navegador do Usuário"]
        FE["🖥️ Vue 3 SPA
        (TypeScript + Vite)
        pages/, components/,
        composables/, stores/"]
    end

    subgraph DockerNetwork["Rede Docker Interna"]
        subgraph NginxContainer["Container: nginx"]
            NGINX["🌐 Nginx
            Proxy reverso
            Serve assets estáticos"]
        end

        subgraph AppContainer["Container: app (PHP-FPM)"]
            subgraph Laravel["Laravel 12"]
                MW["Middleware
                Auth, Throttle, CORS"]
                CTRL["Controllers
                (API/)"]
                SVC["Services
                (Lógica de negócio)"]
                REPO["Repositories
                (Acesso a dados)"]
                MODEL["Models
                (Eloquent)"]
                NOTIF["Notifications
                (Mail)"]
                JOBS["Jobs / Queue
                (assíncrono)"]
                AUDIT["Audit Logger
                (observer/service)"]
            end
        end

        subgraph QueueContainer["Container: queue (worker)"]
            WORKER["Laravel Queue Worker
            Processa jobs de e-mail
            e tarefas assíncronas"]
        end

        subgraph PGContainer["Container: postgres"]
            DB["🐘 PostgreSQL 16
            Banco principal
            Tabela de jobs (queue)"]
        end
    end

    subgraph External["Serviços Externos"]
        SMTP["📧 Servidor SMTP
        (Mailpit dev /
        SES prod)"]
    end

    Browser -->|HTTPS| NginxContainer
    NginxContainer -->|FastCGI| AppContainer
    NginxContainer -->|Arquivos estáticos| AppContainer

    FE -->|API REST JSON /api/| CTRL
    CTRL --> MW
    MW --> CTRL
    CTRL --> SVC
    SVC --> REPO
    REPO --> MODEL
    MODEL --> DB
    SVC --> JOBS
    JOBS --> DB
    WORKER --> DB
    WORKER --> NOTIF
    NOTIF --> SMTP
    CTRL --> AUDIT
    AUDIT --> DB
```

---

## Visão detalhada — Camadas do Laravel

```mermaid
graph LR
    subgraph HTTP["Camada HTTP"]
        REQ["FormRequest
        Validação de entrada"]
        CTRL["Controller
        Roteamento HTTP"]
        RES["API Resource
        Formato de saída"]
    end

    subgraph Business["Camada de Negócio"]
        SVC["Service
        Regras de negócio
        Orquestração"]
        POLICY["Policy
        Autorização por recurso"]
    end

    subgraph Data["Camada de Dados"]
        REPO["Repository
        Abstração de queries"]
        MODEL["Model
        Eloquent ORM
        Relacionamentos"]
    end

    subgraph Support["Suporte"]
        ENUM["Enum
        Status, Perfis"]
        DTO["DTO
        Transferência de dados
        (quando necessário)"]
        EXC["Exception
        Erros de domínio"]
        EVENT["Event + Listener
        Desacoplamento"]
        JOB["Job
        Tarefas assíncronas"]
        NOTIF["Notification
        E-mails"]
    end

    REQ -->|dados validados| CTRL
    CTRL -->|autoriza via| POLICY
    CTRL -->|chama| SVC
    SVC -->|usa| REPO
    REPO -->|consulta| MODEL
    SVC -->|dispara| EVENT
    EVENT -->|processa| JOB
    JOB -->|envia| NOTIF
    CTRL -->|formata via| RES
    SVC -->|lança| EXC
```

---

## Organização dos módulos

| Módulo | Controller | Service | Repository | Model |
|---|---|---|---|---|
| Auth | `AuthController` | `AuthService` | — | `User` |
| Users | `UserController` | `UserService` | `UserRepository` | `User` |
| Doctors | `DoctorController` | `DoctorService` | `DoctorRepository` | `Doctor`, `DoctorSpecialty` |
| Patients | `PatientController` | `PatientService` | `PatientRepository` | `Patient` |
| Specialties | `SpecialtyController` | `SpecialtyService` | `SpecialtyRepository` | `Specialty` |
| Availability | `AvailabilityController` | `AvailabilityService` | `AvailabilityRepository` | `DoctorSchedule`, `DoctorBlock` |
| Appointments | `AppointmentController` | `AppointmentService` | `AppointmentRepository` | `Appointment` |
| Consultations | `ConsultationController` | `ConsultationService` | `ConsultationRepository` | `Appointment`, `ClinicalNote` |
| Dashboard | `DashboardController` | `DashboardService` | — | (múltiplos) |
| Audit | — | `AuditService` | `AuditRepository` | `AuditLog` |
