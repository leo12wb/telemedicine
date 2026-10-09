# Changelog — Sistema de Telemedicina

---

## [Fase 5 — Módulos de negócio] — 2026-10-09

### Adicionado — Módulo: Disponibilidade
- Migration `create_doctor_schedules_table` — horários recorrentes (dia da semana, start/end time, slot_duration_minutes)
- Migration `create_doctor_blocks_table` — bloqueios pontuais (data, início/fim opcionais para dia inteiro)
- Models `DoctorSchedule` e `DoctorBlock` com `HasUuids`, `HasFactory`, `BelongsTo Doctor`
- `Doctor` model: relacionamentos `schedules()`, `blocks()`, `appointments()`
- `Patient` model: relacionamento `appointments()`
- Resources `DoctorScheduleResource` e `DoctorBlockResource` (inclui campo computado `is_all_day`)
- Form Requests: `StoreScheduleRequest`, `UpdateScheduleRequest`, `StoreBlockRequest`
- `AvailabilityService` — `getAvailableSlots()` gera slots a partir de horários recorrentes, subtrai bloqueios e consultas já agendadas
- `AvailabilityController` — 7 actions (CRUD schedules, CRUD blocks, availability)
- Rotas: `GET/POST/PUT/DELETE /api/v1/doctors/{doctor}/schedules`, `GET/POST/DELETE /api/v1/doctors/{doctor}/blocks`, `GET /api/v1/doctors/{doctor}/availability`
- Factories `DoctorScheduleFactory` e `DoctorBlockFactory` com states (`forDoctor`, `inactive`, `allDay`, `partial`)
- 35 testes Feature + 9 testes Unit para AvailabilityService
- Frontend: `types/availability.ts`, `services/availability.ts`, `stores/availability.ts`
- Página `DoctorSchedulePage.vue` — gerenciamento de horários e bloqueios via `route.params.doctorId`
- Página `AvailabilitySearchPage.vue` — busca de slots disponíveis por médico/data
- Rota Vue `/doctors/:doctorId/schedule` e `/availability`
- 8 testes Vitest para o store de disponibilidade

### Adicionado — Módulo: Consultas (Appointments)
- Enum `AppointmentStatus` — `agendada`, `em_andamento`, `concluida`, `cancelada`, `paciente_ausente`; método `isTerminal()`
- Migration `create_appointments_table` — `scheduled_date` (date) + `scheduled_time` (time), `notes`, `cancelled_by`, `started_at`, `ended_at`, soft delete; índices compostos
- Model `Appointment` com `HasUuids`, `SoftDeletes`, cast para enum `AppointmentStatus`, `scopeActive()`
- Resource `AppointmentResource` — doctor/patient inline, `status_label`
- Notifications `AppointmentBooked` e `AppointmentCancelled` (queueable, canal mail)
- `AppointmentService`:
  - `create()` — normalização HH:MM→HH:MM:00, `DB::transaction` + `lockForUpdate()` para anti-double-booking, checagem de 2h de antecedência, disparo de notificações
  - `cancel()` — checagem de estado terminal, prazo de 24h para paciente, notificações
  - `reschedule()` — cancel + create em transação
  - `start()` / `finish()` — transições de estado com timestamps
  - `updateNotes()` — bloqueado em estados terminais
- 5 Form Requests para consultas
- `AppointmentController` — 7 actions
- Rotas: `GET/POST /api/v1/appointments`, `GET/PATCH /api/v1/appointments/{id}`, actions `cancel`, `reschedule`, `start`, `finish`, `notes`
- `AppointmentFactory` com states (`forDoctor`, `forPatient`, `scheduled`, `inProgress`, `concluded`, `cancelled`)
- 35 testes Feature (`AppointmentCrudTest`) + 10 testes Unit (`AppointmentServiceTest`)
- Frontend: `types/appointment.ts` com `STATUS_COLORS`, `services/appointment.ts`, `stores/appointment.ts`
- Página `AppointmentsPage.vue` — listagem com 4 modais (agendar, cancelar, finalizar, notas), seletor de slots, botões de ação por perfil
- Rota Vue `/appointments`
- 6 testes Vitest para o store de consultas

### Adicionado — Módulo: Dashboard
- `DashboardService` — métodos `adminDashboard()`, `doctorDashboard()`, `patientDashboard()` com queries isoladas por perfil
- `DashboardController` — dispara para o método correto via `match($user->role->value)`
- Rota: `GET /api/v1/dashboard` (protegida, retorno varia por perfil)
- 6 testes Feature (`DashboardTest`) — admin, médico, paciente
- Frontend: `services/dashboard.ts` com tipos `AdminDashboard`, `DoctorDashboard`, `PatientDashboard`
- `DashboardPage.vue` — painéis específicos por perfil, componente inline `AppointmentList`
- 3 testes Vitest para o service de dashboard

---

## [Fase 4 — Infraestrutura e módulos base] — 2026-10-09

### Adicionado — Infraestrutura
- Configuração Laravel 12 com PostgreSQL 16 (produção) e SQLite em memória (testes)
- Autenticação via Laravel Sanctum (Bearer token)
- Enum `UserRole` — `admin`, `medico`, `paciente`
- `HasUuids` em todos os models principais
- `SoftDeletes` em `users`, `doctors`, `patients`
- Policies registradas em `AppServiceProvider`
- Configuração Vite + Vue 3 + TypeScript
- Pinia (stores), Vue Router com guards `requiresAuth`/`requiresGuest`/`requiresAdmin`
- Configuração Vitest para testes frontend

### Adicionado — Módulo: Autenticação
- `AuthController` — `register`, `login`, `logout`, `me`, `forgotPassword`, `resetPassword`
- `UserResource` com campo `role`
- Login retorna token Sanctum + dados do usuário
- Rotas públicas: `/auth/register`, `/auth/login`, `/auth/forgot-password`, `/auth/reset-password`
- Frontend: store Pinia `useAuthStore`, serviço `http.ts` (interceptor de token + redirect 401), páginas `LoginPage.vue` e `RegisterPage.vue`

### Adicionado — Módulo: Usuários
- `UserController` — CRUD completo + `toggleActive`
- Políticas de acesso: somente admin
- Frontend: `UsersPage.vue` com modal de criação/edição, `useUserStore`

### Adicionado — Módulo: Especialidades
- `SpecialtyController` — CRUD + `toggleActive` + `active` (listagem pública)
- Frontend: `SpecialtiesPage.vue`, `useSpecialtyStore`

### Adicionado — Módulo: Médicos
- `DoctorController` — CRUD + `toggleActive`, pivot doctor_specialty
- `DoctorResource` com especialidades inline
- Frontend: `DoctorsPage.vue` com link "Agenda" para disponibilidade, `useDoctorStore`

### Adicionado — Módulo: Pacientes
- `PatientController` — CRUD + `profile` (próprio paciente)
- Frontend: `PatientsPage.vue`, `usePatientStore`

---

## [Fases 0–3 — Documentação e planejamento] — 2026-10-09

### Adicionado
- Estrutura inicial da documentação (`docs/`)
- Diagnóstico do repositório (Fase 0)
- Levantamento de requisitos — `docs/requirements/levantamento.md`
- Requisitos funcionais (RF-001 a RF-039) — `docs/requirements/requisitos-funcionais.md`
- Requisitos não funcionais (RNF-001 a RNF-027) — `docs/requirements/requisitos-nao-funcionais.md`
- Regras de negócio (RN-001 a RN-019) — `docs/requirements/regras-de-negocio.md`
- Visão geral da arquitetura — `docs/architecture/visao-geral.md`
- Decisões arquiteturais (ADR-001 a ADR-008) — `docs/architecture/decisoes-arquiteturais.md`
- Diagrama de casos de uso — `docs/architecture/diagramas/casos-de-uso.md`
- Diagrama de componentes — `docs/architecture/diagramas/componentes.md`
- Diagrama de classes — `docs/architecture/diagramas/classes.md`
- Diagramas de sequência (autenticação, agendamento, cancelamento, consulta) — `docs/architecture/diagramas/sequencia.md`
- Diagrama entidade-relacionamento — `docs/architecture/diagramas/entidade-relacionamento.md`
- Diagramas de atividades (agendamento, consulta, cancelamento) — `docs/architecture/diagramas/atividades.md`
- Modelo de dados — `docs/database/modelo-de-dados.md`
- Segurança e privacidade — `docs/security/seguranca-e-privacidade.md`
- Estratégia de testes — `docs/testing/estrategia-de-testes.md`
- Convenções da API — `docs/api/convencoes.md`
- Planejamento de infraestrutura Docker — `docs/deployment/docker.md`
