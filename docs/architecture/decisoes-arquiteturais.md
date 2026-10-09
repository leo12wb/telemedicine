# Decisões Arquiteturais (ADRs) — Sistema de Telemedicina

**Data:** 2026-10-09

---

## ADR-001 — Frontend integrado ao projeto Laravel

**Status:** Aprovado (definido em sistema.md)

**Contexto:** O sistema poderia ter o frontend como um projeto separado (SPA standalone), mas isso aumenta a complexidade de deploy, CI/CD e autenticação.

**Decisão:** O Vue 3 permanece dentro do projeto Laravel, usando Vite como bundler. A API é consumida internamente e poderá ser exposta a outros clientes futuramente.

**Consequências:** Deploy unificado, sem CORS para o frontend próprio, estrutura mais simples no MVP.

---

## ADR-002 — Arquitetura Controller → Service → Repository → Model

**Status:** Aprovado (definido em sistema.md)

**Contexto:** Lógica de negócio concentrada em controllers cria acoplamento e dificulta testes.

**Decisão:** Camadas com responsabilidades distintas: Controller (HTTP), Service (negócio), Repository (persistência), Model (entidade).

**Consequências:** Mais arquivos por módulo, mas maior testabilidade e manutenibilidade.

---

## ADR-003 — Laravel Sanctum para autenticação

**Status:** Proposta — confirmar modo (SPA vs. token)

**Contexto:** O sistema tem um frontend Vue 3 integrado (SPA) e poderá ter clientes externos no futuro.

**Decisão (proposta):** Sanctum em modo token (stateless), emitindo tokens de acesso pessoal. Simplifica integração com clientes externos futuros e evita configuração de CSRF para a API.

**Alternativa rejeitada:** Sanctum SPA (cookie-based) exige configuração de CSRF e domínio; adequado apenas para o frontend próprio.

**Pendente:** Confirmação do modo de autenticação pelo responsável do projeto.

---

## ADR-004 — PostgreSQL como banco de dados único

**Status:** Aprovado (definido em sistema.md)

**Contexto:** Dados de saúde exigem integridade transacional e suporte a consultas complexas.

**Decisão:** PostgreSQL 16 como única fonte de dados persistente no MVP.

**Consequências:** Constraints de unicidade e integridade referencial no banco; necessidade de estratégia de concorrência para agendamentos.

---

## ADR-005 — Queue driver: database no MVP

**Status:** Proposta

**Contexto:** Notificações por e-mail devem ser assíncronas para não impactar o tempo de resposta da API.

**Decisão (proposta):** Driver `database` para filas no MVP. Simples, sem dependência adicional de infraestrutura (Redis/Beanstalkd).

**Risco:** Menor throughput em carga alta. Aceitável para o MVP.

**Evolução prevista:** Migração para Redis em V2 conforme necessidade.

---

## ADR-006 — Versionamento da API

**Status:** Pendente de decisão

**Opção A:** Sem versão explícita no MVP (`/api/consultas`). Mais simples agora, risco de breaking changes futuras.

**Opção B:** Prefixo desde o início (`/api/v1/consultas`). Overhead mínimo, facilita evolução sem quebrar clientes.

**Recomendação:** Opção B — custo de adoção é baixo e evita refatoração futura.

---

## ADR-007 — Estratégia de concorrência no agendamento

**Status:** Proposta

**Contexto:** Dois pacientes podem tentar agendar o mesmo slot simultaneamente.

**Decisão (proposta):** `SELECT FOR UPDATE` no slot de disponibilidade dentro de uma transação ao confirmar o agendamento. Fallback: constraint única no banco (`doctor_id` + `datetime`).

**Alternativa:** Pessimistic locking via Eloquent (`lockForUpdate()`).

---

## ADR-008 — Soft Delete para entidades críticas

**Status:** Proposta

**Contexto:** Exclusão física de médicos ou pacientes pode violar integridade do histórico de consultas.

**Decisão (proposta):** Utilizar `SoftDeletes` do Laravel para `users`, `doctors`, `patients`. Consultas e anotações nunca excluídas fisicamente.

**Consequências:** Histórico preservado; necessidade de escopos de query para excluir registros deletados.
