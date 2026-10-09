# Modelo de Dados — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Implementado (Fase 5)

---

## Tabelas principais (MVP)

### `users`
Armazena todos os usuários do sistema, independentemente do perfil.

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| `id` | uuid | PK, default gen_random_uuid() | Identificador único |
| `name` | varchar(255) | NOT NULL | Nome completo |
| `email` | varchar(255) | NOT NULL, UNIQUE | E-mail (login) |
| `password` | varchar(255) | NOT NULL | Hash bcrypt |
| `role` | varchar(20) | NOT NULL | `admin`, `medico`, `paciente` |
| `is_active` | boolean | NOT NULL, default true | Conta ativa |
| `email_verified_at` | timestamp | NULL | Data de verificação |
| `remember_token` | varchar(100) | NULL | Token de sessão |
| `created_at` | timestamp | NOT NULL | — |
| `updated_at` | timestamp | NOT NULL | — |
| `deleted_at` | timestamp | NULL | Soft delete |

**Índices:** `email` (unique), `role`, `is_active`

---

### `doctors`
Dados específicos do perfil médico.

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| `id` | uuid | PK | — |
| `user_id` | uuid | FK users.id, NOT NULL | Usuário vinculado |
| `crm` | varchar(20) | NOT NULL | Número do CRM |
| `crm_uf` | char(2) | NOT NULL | UF do CRM |
| `phone` | varchar(20) | NULL | Telefone |
| `bio` | text | NULL | Mini-bio |
| `photo_path` | varchar(500) | NULL | Caminho da foto |
| `is_active` | boolean | NOT NULL, default true | Ativo no sistema |
| `created_at` | timestamp | NOT NULL | — |
| `updated_at` | timestamp | NOT NULL | — |
| `deleted_at` | timestamp | NULL | Soft delete |

**Índices:** `user_id`, unique(`crm`, `crm_uf`), `is_active`

---

### `patients`
Dados específicos do perfil paciente.

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| `id` | uuid | PK | — |
| `user_id` | uuid | FK users.id, NOT NULL | Usuário vinculado |
| `cpf` | varchar(14) | NULL, UNIQUE | CPF (opcional no MVP) |
| `birth_date` | date | NULL | Data de nascimento |
| `phone` | varchar(20) | NULL | Telefone |
| `health_insurance` | varchar(100) | NULL | Convênio |
| `health_insurance_number` | varchar(50) | NULL | Número do convênio |
| `created_at` | timestamp | NOT NULL | — |
| `updated_at` | timestamp | NOT NULL | — |
| `deleted_at` | timestamp | NULL | Soft delete |

**Índices:** `user_id`, `cpf` (unique quando preenchido)

---

### `specialties`
Lista de especialidades médicas gerenciadas pelo admin.

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| `id` | uuid | PK | — |
| `name` | varchar(100) | NOT NULL, UNIQUE | Nome da especialidade |
| `description` | text | NULL | — |
| `is_active` | boolean | NOT NULL, default true | — |
| `created_at` | timestamp | NOT NULL | — |
| `updated_at` | timestamp | NOT NULL | — |

---

### `doctor_specialty` (pivot)
Relacionamento N:N entre médicos e especialidades.

| Coluna | Tipo | Restrições |
|---|---|---|
| `doctor_id` | uuid | FK doctors.id |
| `specialty_id` | uuid | FK specialties.id |

**PK:** (`doctor_id`, `specialty_id`)

---

### `doctor_schedules`
Horários recorrentes de atendimento do médico.

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| `id` | uuid | PK | — |
| `doctor_id` | uuid | FK doctors.id, NOT NULL | — |
| `day_of_week` | smallint | NOT NULL, 0–6 | 0=Dom, 1=Seg... |
| `start_time` | time | NOT NULL | Início do atendimento |
| `end_time` | time | NOT NULL | Fim do atendimento |
| `slot_duration_minutes` | smallint | NOT NULL, default 30 | Duração de cada slot |
| `is_active` | boolean | NOT NULL, default true | — |
| `created_at` | timestamp | NOT NULL | — |
| `updated_at` | timestamp | NOT NULL | — |

**Índices:** `doctor_id`, (`doctor_id`, `day_of_week`)

---

### `doctor_blocks`
Bloqueios pontuais na agenda do médico.

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| `id` | uuid | PK | — |
| `doctor_id` | uuid | FK doctors.id, NOT NULL | — |
| `block_date` | date | NOT NULL | Data do bloqueio |
| `block_start` | time | NULL | Início (null = dia inteiro) |
| `block_end` | time | NULL | Fim (null = dia inteiro) |
| `reason` | varchar(255) | NULL | Motivo |
| `created_at` | timestamp | NOT NULL | — |

**Índices:** (`doctor_id`, `block_date`)

---

### `appointments`
Consultas agendadas.

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| `id` | uuid | PK | — |
| `doctor_id` | uuid | FK doctors.id, NOT NULL | — |
| `patient_id` | uuid | FK patients.id, NOT NULL | — |
| `scheduled_date` | date | NOT NULL | Data da consulta |
| `scheduled_time` | time | NOT NULL | Horário da consulta (HH:MM:SS) |
| `duration_minutes` | smallint | NOT NULL, default 30 | — |
| `status` | varchar(20) | NOT NULL, default 'agendada' | Ver enum AppointmentStatus |
| `notes` | text | NULL | Anotações clínicas do médico |
| `cancellation_reason` | text | NULL | — |
| `cancelled_by` | uuid | FK users.id, NULL | Quem cancelou |
| `started_at` | timestamp | NULL | Início real |
| `ended_at` | timestamp | NULL | Fim real |
| `created_at` | timestamp | NOT NULL | — |
| `updated_at` | timestamp | NOT NULL | — |
| `deleted_at` | timestamp | NULL | Soft delete |

**Índices:** `doctor_id`, `patient_id`, `status`, (`doctor_id`, `scheduled_date`, `scheduled_time`)

**Constraint de concorrência:** verificação transacional em `AppointmentService::create()` com `lockForUpdate()` — impede double-booking em requisições simultâneas para o mesmo médico/horário.

**Enum `AppointmentStatus`:** `agendada`, `em_andamento`, `concluida`, `cancelada`, `paciente_ausente`. Estados terminais (`concluida`, `cancelada`, `paciente_ausente`) bloqueiam edições posteriores.

---

### `audit_logs`
Registro imutável de operações sensíveis.

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| `id` | uuid | PK | — |
| `user_id` | uuid | FK users.id, NULL | Quem executou (null=sistema) |
| `action` | varchar(100) | NOT NULL | Ex: `login`, `appointment.created` |
| `entity_type` | varchar(100) | NULL | Ex: `App\Models\Appointment` |
| `entity_id` | uuid | NULL | ID da entidade afetada |
| `old_values` | jsonb | NULL | Estado anterior |
| `new_values` | jsonb | NULL | Estado posterior |
| `ip_address` | varchar(45) | NULL | IPv4/IPv6 |
| `user_agent` | text | NULL | — |
| `created_at` | timestamp | NOT NULL | — |

**Índices:** `user_id`, `action`, `entity_type`, `created_at`

**Nota:** Registros de auditoria nunca devem ser deletados pelo sistema.

---

## Tabelas do framework Laravel

- `personal_access_tokens` — tokens Sanctum
- `jobs` — fila de jobs (driver database)
- `failed_jobs` — jobs com falha
- `password_reset_tokens` — recuperação de senha
- `migrations` — controle de migrations
- `cache` — cache em banco (se utilizado)
