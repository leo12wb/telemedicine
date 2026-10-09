# Documentação — Sistema de Telemedicina

**Data do planejamento:** 2026-10-09
**Fase atual:** Fase 1 concluída + Fase 2 (proposta inicial)
**Status:** Aguardando aprovação para iniciar a Fase 4

---

## Diagnóstico do repositório (Fase 0)

| Item | Resultado |
|---|---|
| Projeto Laravel existente | Não — repositório vazio |
| Arquivos de configuração | Apenas `sistema.md` |
| Migrations existentes | Nenhuma |
| Testes existentes | Nenhum |
| Código legado | Nenhum |
| Riscos de segurança imediatos | Não aplicável (repositório vazio) |
| Estado do Git | Branch `main` sem commits |
| Vídeo de referência | Não acessível (YouTube — limitação de ferramenta) |
| PHP disponível | 8.2.12 ✅ |
| Composer disponível | 2.8.5 ✅ |
| Docker disponível | 27.4.0 ✅ |
| Node.js disponível | 22.x ✅ |

**Conclusão:** repositório vazio, sem restrições de código legado. O projeto será criado do zero.

---

## Estrutura desta documentação

```
docs/
├── README.md                           ← este arquivo
├── requirements/
│   ├── levantamento.md                 ← contexto, premissas e dúvidas
│   ├── requisitos-funcionais.md        ← RF-001 a RF-039
│   ├── requisitos-nao-funcionais.md    ← RNF-001 a RNF-027
│   └── regras-de-negocio.md            ← RN-001 a RN-019
├── architecture/
│   ├── visao-geral.md                  ← arquitetura, módulos, stack
│   ├── decisoes-arquiteturais.md       ← ADR-001 a ADR-008
│   └── diagramas/
│       ├── casos-de-uso.md             ← Mermaid
│       ├── componentes.md              ← Mermaid C4
│       ├── classes.md                  ← Mermaid
│       ├── sequencia.md                ← Mermaid (4 fluxos)
│       ├── entidade-relacionamento.md  ← Mermaid ER
│       └── atividades.md               ← Mermaid (3 fluxos)
├── database/
│   └── modelo-de-dados.md              ← tabelas, colunas, índices
├── api/
│   └── convencoes.md                   ← padrões REST, endpoints planejados
├── security/
│   └── seguranca-e-privacidade.md      ← LGPD, RBAC, ameaças
├── testing/
│   └── estrategia-de-testes.md        ← ferramentas, cobertura, cenários
├── deployment/
│   └── docker.md                       ← serviços, volumes, comandos
├── modules/
│   └── (criado por módulo na Fase 5)
└── changelog.md
```

---

## Resumo do planejamento

### Atores e perfis de acesso

| Perfil | Descrição |
|---|---|
| **Administrador** | Gestão completa do sistema — usuários, médicos, especialidades, dashboards, auditoria |
| **Médico** | Disponibilidade, atendimento, anotações clínicas, agenda |
| **Paciente** | Agendamento, histórico, perfil próprio |

### Proposta de MVP

**Módulos do MVP:**
1. Auth (registro, login, verificação de e-mail, recuperação de senha)
2. Users + Perfis
3. Especialidades médicas
4. Médicos (cadastro, edição, desativação)
5. Pacientes (cadastro, perfil)
6. Disponibilidade médica (horários e bloqueios)
7. Agendamento de consultas (agendar, cancelar, reagendar)
8. Realização de consulta (início, anotações, encerramento)
9. Histórico de consultas
10. Dashboards por perfil
11. Notificações por e-mail (confirmação, cancelamento)
12. Auditoria básica

**Fora do MVP (V2/Futuro):** videochamada, prontuário completo, prescrição digital, upload de exames, pagamentos, multi-tenancy.

### Arquitetura proposta

- **Backend:** PHP 8.2 + Laravel 12, camadas Controller → Service → Repository → Model
- **Frontend:** Vue 3 + TypeScript + Vite, integrado ao Laravel
- **Banco:** PostgreSQL 16 com UUIDs, soft deletes, constraint de concorrência em agendamentos
- **Auth:** Laravel Sanctum (token stateless)
- **Fila:** driver `database` no MVP
- **Infra:** Docker Compose com Nginx + PHP-FPM + PostgreSQL

---

## Principais dúvidas pendentes

| ID | Questão | Impacto |
|---|---|---|
| D-001 | Videochamada no MVP? | Alto |
| D-002 | Perfil "Recepcionista"? | Médio |
| D-003 | Médico se auto-cadastra ou somente admin? | Alto |
| D-004 | Pagamento no escopo? | Alto |
| D-005 | Prontuário completo ou anotações simples? | Alto |
| D-007 | Sistema mono-clínica ou multi-clínica? | Alto |
| D-010 | Política de cancelamento (prazo em horas)? | Médio |
| D-013 | Antecedência mínima para agendamento? | Médio |

---

## Principais riscos técnicos e de segurança

| Risco | Mitigação |
|---|---|
| Double-booking em agendamentos simultâneos | `SELECT FOR UPDATE` + constraint única no PostgreSQL |
| Acesso indevido a dados clínicos | Policies de ownership + UUIDs (não enumeráveis) |
| Conformidade LGPD sem validação jurídica | Itens marcados como [PENDENTE JURÍDICO] — não declarar conformidade |
| Conformidade CFM sem responsável médico | Idem — requer consultor qualificado |
| Segredos expostos no repositório | `.env` no `.gitignore`, `.env.example` sem valores reais |
| Ausência de testes de concorrência | Cenário obrigatório na estratégia de testes |

---

## Critérios de conclusão por fase

| Fase | Critério |
|---|---|
| Fase 0 | Repositório inspecionado, diagnóstico documentado |
| Fase 1 | Requisitos, regras de negócio e dúvidas documentados ✅ |
| Fase 2 | Arquitetura, diagramas e modelo de dados propostos ✅ (parcial) |
| Fase 3 | Aprovação do planejamento pelo responsável |
| Fase 4 | Ambiente Docker inicializando corretamente com testes executáveis |
| Fase 5 | Cada módulo: migrations, services, controllers, testes passando, documentação atualizada |
| Fase 6 | Integração completa testada end-to-end |
| Fase 7 | Relatório final com funcionalidades, limitações e próximos passos |

---

## Próximo passo

**Aguardando aprovação deste planejamento para avançar para a Fase 4.**

Se houver decisões pendentes listadas acima que precisam ser resolvidas antes, por favor informe as respostas para que a arquitetura seja ajustada antes da implementação.
