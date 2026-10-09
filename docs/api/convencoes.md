# Convenções da API — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Implementado (Fase 5)

---

## 1. Padrões gerais

- **Base URL:** `/api/v1/` (prefixo de versão desde o início — ver ADR-006)
- **Formato:** JSON exclusivamente (`Content-Type: application/json`)
- **Autenticação:** Bearer token (`Authorization: Bearer {token}`)
- **Codificação:** UTF-8

---

## 2. Convenções de nomenclatura

- Recursos em **kebab-case** e **plural**: `/api/v1/appointments`, `/api/v1/doctor-schedules`
- Ações que não se encaixam em CRUD usam verbos como segmento: `/api/v1/appointments/{id}/cancel`
- Parâmetros de query em **snake_case**: `?specialty_id=`, `?scheduled_date=`

---

## 3. Métodos HTTP

| Operação | Método | Exemplo |
|---|---|---|
| Listar | GET | `GET /api/v1/appointments` |
| Buscar um | GET | `GET /api/v1/appointments/{id}` |
| Criar | POST | `POST /api/v1/appointments` |
| Atualizar (parcial) | PATCH | `PATCH /api/v1/appointments/{id}` |
| Atualizar (total) | PUT | `PUT /api/v1/doctors/{id}` |
| Excluir | DELETE | `DELETE /api/v1/specialties/{id}` |
| Ação customizada | PATCH/POST | `PATCH /api/v1/appointments/{id}/cancel` |

---

## 4. Códigos de resposta

| Código | Uso |
|---|---|
| 200 | Sucesso (GET, PUT, PATCH) |
| 201 | Criado com sucesso (POST) |
| 204 | Sucesso sem corpo (DELETE) |
| 400 | Requisição malformada |
| 401 | Não autenticado |
| 403 | Não autorizado (sem permissão) |
| 404 | Recurso não encontrado |
| 409 | Conflito (ex: double-booking) |
| 422 | Erro de validação |
| 429 | Rate limit excedido |
| 500 | Erro interno do servidor |

---

## 5. Formato de resposta

### Sucesso com recurso único
```json
{
  "data": {
    "id": "uuid",
    "..." : "..."
  }
}
```

### Sucesso com lista paginada
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

### Erro de validação (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["O e-mail é obrigatório."],
    "scheduled_time": ["O horário selecionado não está disponível."]
  }
}
```

### Erro genérico
```json
{
  "message": "Descrição do erro."
}
```

---

## 6. Paginação

- Padrão: 15 itens por página.
- Parâmetros: `?page=1&per_page=15`
- Todas as listagens com potencial de crescimento devem ser paginadas.

---

## 7. Filtros e ordenação

- Filtros via query string: `?status=agendada&doctor_id=uuid`
- Ordenação: `?sort=scheduled_date&direction=asc`

---

## 8. Endpoints planejados (MVP)

### Auth
| Método | Endpoint | Descrição | Auth |
|---|---|---|---|
| POST | `/api/v1/auth/register` | Registro de paciente | Não |
| POST | `/api/v1/auth/login` | Login | Não |
| POST | `/api/v1/auth/logout` | Logout | Sim |
| POST | `/api/v1/auth/forgot-password` | Solicitar recuperação | Não |
| POST | `/api/v1/auth/reset-password` | Redefinir senha | Não |
| GET | `/api/v1/auth/me` | Dados do usuário logado | Sim |

### Users (Admin)
| Método | Endpoint | Auth | Perfil |
|---|---|---|---|
| GET | `/api/v1/users` | Sim | admin |
| GET | `/api/v1/users/{id}` | Sim | admin |
| POST | `/api/v1/users` | Sim | admin |
| PUT | `/api/v1/users/{id}` | Sim | admin |
| PATCH | `/api/v1/users/{id}/toggle-active` | Sim | admin |
| DELETE | `/api/v1/users/{id}` | Sim | admin |

### Doctors
| Método | Endpoint | Perfil |
|---|---|---|
| GET | `/api/v1/doctors` | admin, medico, paciente |
| GET | `/api/v1/doctors/{id}` | admin, medico, paciente |
| POST | `/api/v1/doctors` | admin |
| PUT | `/api/v1/doctors/{id}` | admin |
| PATCH | `/api/v1/doctors/{id}/toggle-active` | admin |
| DELETE | `/api/v1/doctors/{id}` | admin |

### Specialties
| Método | Endpoint | Perfil |
|---|---|---|
| GET | `/api/v1/specialties/active` | todos |
| GET | `/api/v1/specialties` | admin |
| POST | `/api/v1/specialties` | admin |
| PUT | `/api/v1/specialties/{id}` | admin |
| PATCH | `/api/v1/specialties/{id}/toggle-active` | admin |
| DELETE | `/api/v1/specialties/{id}` | admin |

### Availability
| Método | Endpoint | Perfil |
|---|---|---|
| GET | `/api/v1/doctors/{id}/schedules` | admin, medico (próprio) |
| POST | `/api/v1/doctors/{id}/schedules` | admin, medico (próprio) |
| PUT | `/api/v1/doctors/{id}/schedules/{sid}` | admin, medico (próprio) |
| DELETE | `/api/v1/doctors/{id}/schedules/{sid}` | admin, medico (próprio) |
| GET | `/api/v1/doctors/{id}/availability` | todos (query: `?date=YYYY-MM-DD`) |
| GET | `/api/v1/doctors/{id}/blocks` | admin, medico (próprio) |
| POST | `/api/v1/doctors/{id}/blocks` | admin, medico (próprio) |
| DELETE | `/api/v1/doctors/{id}/blocks/{bid}` | admin, medico (próprio) |

### Appointments
| Método | Endpoint | Perfil |
|---|---|---|
| GET | `/api/v1/appointments` | admin (todos), medico (suas), paciente (suas) |
| GET | `/api/v1/appointments/{id}` | admin, medico (própria), paciente (própria) |
| POST | `/api/v1/appointments` | paciente |
| PATCH | `/api/v1/appointments/{id}/cancel` | admin, medico (suas), paciente (prazo 24h) |
| PATCH | `/api/v1/appointments/{id}/reschedule` | paciente |
| PATCH | `/api/v1/appointments/{id}/start` | medico (própria) — agendada → em_andamento |
| PATCH | `/api/v1/appointments/{id}/finish` | medico (própria) — em_andamento → concluida/paciente_ausente |
| PATCH | `/api/v1/appointments/{id}/notes` | medico (própria) — adiciona/edita anotações clínicas |

### Dashboard
| Método | Endpoint | Perfil | Retorno |
|---|---|---|---|
| GET | `/api/v1/dashboard` | admin | `totals`, `appointments_by_status`, `today_appointments`, `upcoming_appointments` |
| GET | `/api/v1/dashboard` | medico | `today_appointments`, `upcoming_appointments`, `week_summary`, `absent_this_week` |
| GET | `/api/v1/dashboard` | paciente | `upcoming_appointments`, `recent_history`, `total_concluded` |
