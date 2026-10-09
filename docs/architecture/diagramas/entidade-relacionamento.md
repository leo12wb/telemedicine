# Diagrama Entidade-Relacionamento — Sistema de Telemedicina

**Data:** 2026-10-09
**Formato:** Mermaid ER
**Status:** Implementado (Fase 5)

---

```mermaid
erDiagram
    users {
        uuid id PK
        string name
        string email UK
        string password
        string role "admin|medico|paciente"
        timestamp email_verified_at
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    doctors {
        uuid id PK
        uuid user_id FK
        string crm UK "CRM único por UF"
        string crm_uf
        string phone
        string bio
        string photo_path
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    patients {
        uuid id PK
        uuid user_id FK
        string cpf UK
        date birth_date
        string phone
        string health_insurance
        string health_insurance_number
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    specialties {
        uuid id PK
        string name UK
        string description
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    doctor_specialty {
        uuid doctor_id FK
        uuid specialty_id FK
    }

    doctor_schedules {
        uuid id PK
        uuid doctor_id FK
        integer day_of_week "0=Dom, 1=Seg..."
        time start_time
        time end_time
        integer slot_duration_minutes
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    doctor_blocks {
        uuid id PK
        uuid doctor_id FK
        date block_date
        time block_start "null = dia inteiro"
        time block_end
        string reason
        timestamp created_at
    }

    appointments {
        uuid id PK
        uuid doctor_id FK
        uuid patient_id FK
        date scheduled_date
        time scheduled_time
        integer duration_minutes
        string status "agendada|em_andamento|concluida|cancelada|paciente_ausente"
        text notes "anotações clínicas do médico"
        string cancellation_reason
        uuid cancelled_by FK "user_id"
        timestamp started_at
        timestamp ended_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    audit_logs {
        uuid id PK
        uuid user_id FK "null para sistema"
        string action
        string entity_type
        uuid entity_id
        jsonb old_values
        jsonb new_values
        string ip_address
        string user_agent
        timestamp created_at
    }

    password_reset_tokens {
        string email PK
        string token
        timestamp created_at
    }

    personal_access_tokens {
        bigint id PK
        string tokenable_type
        uuid tokenable_id
        string name
        string token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    jobs {
        bigint id PK
        string queue
        text payload
        integer attempts
        integer reserved_at
        integer available_at
        integer created_at
    }

    %% Relacionamentos
    users ||--o| doctors : "tem perfil"
    users ||--o| patients : "tem perfil"
    doctors ||--o{ doctor_specialty : "tem"
    specialties ||--o{ doctor_specialty : "pertence a"
    doctors ||--o{ doctor_schedules : "define"
    doctors ||--o{ doctor_blocks : "bloqueia"
    doctors ||--o{ appointments : "realiza"
    patients ||--o{ appointments : "agenda"
    users ||--o{ audit_logs : "gera"
```

---

## Notas sobre o modelo

1. **UUIDs** como chaves primárias em todas as tabelas principais (evita enumeração de IDs).
2. **Soft delete** (`deleted_at`) em `users`, `doctors`, `patients` e `appointments` para preservar histórico.
3. **Anti double-booking:** verificação transacional com `lockForUpdate()` em `AppointmentService::create()` — impede agendamentos simultâneos no mesmo horário para o mesmo médico.
4. **`appointments.notes`** armazena as anotações clínicas do médico diretamente na consulta (sem tabela separada no MVP).
5. **`appointments.scheduled_date` + `scheduled_time`** substituem `scheduled_at` para facilitar queries por data e consultas de disponibilidade.
6. **`audit_logs`** com `jsonb` para flexibilidade nos valores capturados.
7. **`doctor_blocks`** com `block_start`/`block_end` nulos indica bloqueio do dia inteiro.
8. O campo `role` em `users` define o perfil de acesso (RBAC simples para MVP).
