# Visão Geral da Arquitetura — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Implementado

---

## 1. Estilo arquitetural

O sistema adota uma **arquitetura monolítica em camadas** com separação clara de responsabilidades, seguindo o padrão:

```
Requisição HTTP
    ↓
[Nginx] → [PHP-FPM] → [Laravel]
                           ↓
                      [Middleware]
                      (Auth, Throttle, CORS)
                           ↓
                      [Controller]
                      (HTTP in/out, Form Request)
                           ↓
                      [Service]
                      (Regras de negócio, orquestração)
                           ↓
                      [Repository]
                      (Abstração de persistência)
                           ↓
                      [Model / Eloquent]
                           ↓
                      [PostgreSQL]
```

O **frontend Vue 3** reside dentro do mesmo projeto Laravel e é servido pelo Vite (desenvolvimento) e como assets compilados (produção), via Nginx.

---

## 2. Stack tecnológica

| Componente | Tecnologia | Versão |
|---|---|---|
| Backend | PHP + Laravel | 8.2 / 12.x |
| Frontend | Vue.js + TypeScript | 3.x |
| Build frontend | Vite | Mais recente compatível com Laravel 12 |
| Banco de dados | PostgreSQL | 16.x |
| Servidor web | Nginx | 1.25+ |
| Runtime PHP | PHP-FPM | 8.2 |
| Autenticação | Laravel Sanctum | — |
| Autorização | Laravel Policies + Gates | — |
| Fila | Laravel Queue (database driver no MVP) | — |
| Notificações | Laravel Notifications (Mail) | — |
| Testes backend | PHPUnit / Pest | — |
| Testes frontend | Vitest + Vue Test Utils | — |
| Containerização | Docker + Docker Compose | — |

---

## 3. Módulos do sistema

```
Sistema de Telemedicina
├── Auth              → Autenticação, registro, recuperação de senha
├── Users             → Gerenciamento de usuários e perfis
├── Doctors           → Cadastro e perfil de médicos
├── Patients          → Cadastro e perfil de pacientes
├── Specialties       → Especialidades médicas
├── Availability      → Horários e disponibilidade médica
├── Appointments      → Agendamento, cancelamento, reagendamento
├── Consultations     → Realização, anotações, encerramento
├── History           → Histórico de consultas
├── Notifications     → E-mails de confirmação e alertas
├── Audit             → Log de auditoria de operações sensíveis
└── Dashboard         → Painéis por perfil de usuário
```

---

## 4. Estratégia de autenticação e autorização

### Autenticação
- **Laravel Sanctum** com tokens de API (SPA mode ou token-based).
- Tokens com expiração configurável via `.env`.
- Verificação de e-mail obrigatória antes de acessar recursos protegidos.

### Autorização (RBAC)
- Perfis: `admin`, `medico`, `paciente`.
- Implementado via **Laravel Policies** para recursos específicos e **Gates** para permissões globais.
- Middleware `role:admin` / `role:medico` / `role:paciente` para proteção de rotas.

### Tabela de permissões por perfil (resumo)

| Recurso | Admin | Médico | Paciente |
|---|---|---|---|
| CRUD usuários | ✅ | ❌ | ❌ (próprio perfil) |
| CRUD médicos | ✅ | ❌ | ❌ |
| CRUD pacientes | ✅ | ❌ | ❌ (próprio perfil) |
| CRUD especialidades | ✅ | ❌ | ❌ |
| Gerenciar disponibilidade | ✅ | ✅ (própria) | ❌ |
| Agendar consulta | ✅ | ❌ | ✅ |
| Cancelar consulta | ✅ | ✅ (suas) | ✅ (prazo) |
| Iniciar/encerrar consulta | ❌ | ✅ (suas) | ❌ |
| Anotações clínicas | ❌ | ✅ (suas consultas) | Leitura própria |
| Dashboard | ✅ | ✅ (próprio) | ✅ (próprio) |
| Logs de auditoria | ✅ | ❌ | ❌ |

---

## 5. Organização de pastas do backend (Laravel)

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── Auth/
│   │       ├── Admin/
│   │       ├── Doctor/
│   │       └── Patient/
│   ├── Requests/         → Form Requests por módulo
│   ├── Resources/        → API Resources por módulo
│   └── Middleware/
├── Services/             → Lógica de negócio
├── Repositories/         → Acesso a dados
│   └── Contracts/        → Interfaces dos repositories
├── Models/
├── Policies/
├── Enums/
├── Exceptions/
├── Notifications/
├── Jobs/
├── Events/
├── Listeners/
└── DTOs/                 → Quando necessário
```

---

## 6. Organização de pastas do frontend (Vue 3)

```
resources/
├── js/
│   ├── pages/            → Páginas por módulo
│   ├── components/       → Componentes reutilizáveis
│   ├── composables/      → Hooks Vue (useAuth, useAppointments...)
│   ├── services/         → Chamadas à API (axios)
│   ├── stores/           → Pinia stores (estado global)
│   ├── types/            → Interfaces TypeScript
│   ├── router/           → Vue Router
│   └── layouts/          → Layouts base (admin, médico, paciente)
└── css/
```

---

## 7. Infraestrutura Docker

```
Serviços Docker (compose.yaml):
├── app        → PHP-FPM 8.2 (código Laravel)
├── nginx      → Nginx (proxy reverso, servindo assets)
├── postgres   → PostgreSQL 16
└── queue      → Laravel worker (mesmo container app ou dedicado)
```

Sem exposição do PostgreSQL na rede pública. Comunicação interna via rede Docker.

---

## 8. Decisões arquiteturais pendentes de confirmação

| Decisão | Opção A (sugerida) | Opção B | Impacto |
|---|---|---|---|
| Driver de fila | `database` (MVP simples) | `redis` (mais robusto) | Complexidade de infra |
| Autenticação SPA | Sanctum SPA (cookie) | Sanctum token (stateless) | CSRF vs simplicidade |
| ORM | Eloquent puro | + Repository | Já definido em sistema.md |
| Prontuário | Anotações simples (text) | Estrutura SOAP/FHIR | Complexidade |
| Versionamento API | Sem versão no MVP | `/api/v1/` desde o início | Mudanças futuras |

---

## 9. Diagramas a serem produzidos

1. Diagrama de casos de uso (Mermaid)
2. Diagrama de componentes (Mermaid C4)
3. Diagrama de classes do domínio (Mermaid)
4. Diagrama de sequência — Autenticação (Mermaid)
5. Diagrama de sequência — Agendamento de consulta (Mermaid)
6. Diagrama de sequência — Cancelamento (Mermaid)
7. Diagrama de sequência — Realização de consulta (Mermaid)
8. Diagrama entidade-relacionamento (Mermaid ER)
9. Diagrama de atividades — Agendamento (Mermaid)
10. Diagrama de atividades — Realização de consulta (Mermaid)
