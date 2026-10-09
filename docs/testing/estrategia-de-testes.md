# Estratégia de Testes — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Implementado

---

## 1. Ferramentas

| Camada | Ferramenta |
|---|---|
| Backend (unitários e de integração) | Pest PHP (wrapper expressivo sobre PHPUnit) |
| Backend (feature/HTTP) | Pest + Laravel HTTP Testing |
| Frontend (unitários e componentes) | Vitest + Vue Test Utils |
| Banco de dados em testes | SQLite em memória (padrão) ou PostgreSQL separado |
| Factories | Laravel Factories com Faker |
| Seeders de teste | DatabaseSeeder dedicado para ambiente de testes |

---

## 2. Tipos de teste por camada

### 2.1. Testes de unidade (Services e regras de negócio)
- Testam a lógica de negócio em isolamento, com repositórios mockados.
- Foco: validações, transições de status, cálculos, regras de negócio.
- Localização: `tests/Unit/Services/`

### 2.2. Testes de integração (Repository + Model)
- Testam a interação real com o banco de dados.
- Usam o trait `RefreshDatabase`.
- Localização: `tests/Integration/`

### 2.3. Testes de feature (API HTTP)
- Testam endpoints completos: autenticação, autorização, resposta, validação.
- Usam `actingAs()` e `assertJson()`.
- Localização: `tests/Feature/`

### 2.4. Testes de frontend (componentes Vue)
- Testam comportamento de componentes em isolamento com estados simulados.
- Localização: `resources/js/__tests__/`

---

## 3. Cobertura obrigatória por módulo (MVP)

| Módulo | Testes de unidade | Testes de feature | Autorização |
|---|---|---|---|
| Auth | ✅ | ✅ | — |
| Users | ✅ | ✅ | ✅ |
| Doctors | ✅ | ✅ | ✅ |
| Patients | ✅ | ✅ | ✅ |
| Specialties | ✅ | ✅ | ✅ |
| Availability | ✅ | ✅ | ✅ |
| Appointments | ✅ (crítico) | ✅ | ✅ |
| Consultations | ✅ | ✅ | ✅ |
| Audit | — | ✅ | — |

---

## 4. Cenários críticos obrigatórios

### Segurança e autorização
- Acesso a consulta de outro paciente → 403
- Médico acessando consulta de outro médico → 403
- Endpoint protegido sem token → 401
- Token expirado → 401
- Paciente tentando cancelar consulta de outro paciente → 403

### Agendamento e concorrência
- Agendamento em slot disponível → 201
- Agendamento em slot ocupado → 409
- Dois agendamentos simultâneos no mesmo slot → apenas um sucesso
- Agendamento com antecedência insuficiente → 422
- Cancelamento dentro do prazo → 200
- Cancelamento fora do prazo (paciente) → 422
- Reagendamento para slot disponível → 201

### Validação
- Campos obrigatórios ausentes → 422
- E-mail inválido → 422
- CRM duplicado por UF → 422
- Login com credenciais inválidas → 401
- Registro com e-mail já existente → 422

### Regras de negócio
- Status de consulta só avança conforme fluxo permitido → 422 para transições inválidas
- Médico inativo não aparece na busca → verificar nos resultados
- Slot bloqueado não aparece como disponível → verificar

---

## 5. Dados de teste

- Factories para: `User`, `Doctor`, `Patient`, `Specialty`, `DoctorSchedule`, `Appointment`, `ClinicalNote`
- Estados de factory: `withDoctor()`, `withPatient()`, `agendada()`, `concluida()`, `cancelada()`
- **Proibido**: dados clínicos reais, CPFs reais, e-mails reais em qualquer factory ou seeder.

---

## 6. Regras de qualidade

1. Nenhum teste é marcado como passando sem execução real.
2. Não remover verificações para fazer testes passarem.
3. Cada PR deve incluir testes para as funcionalidades adicionadas.
4. Testes devem ser independentes — sem dependência de ordem de execução.
5. Testes não devem depender de dados externos ou de APIs reais.
