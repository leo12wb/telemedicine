# Levantamento de Requisitos — Sistema de Telemedicina

**Data:** 2026-10-09
**Fase:** Fase 1 — Levantamento de Requisitos
**Status:** Proposta inicial — aguardando aprovação

---

## 1. Contexto do projeto

O sistema de telemedicina tem como objetivo conectar pacientes a profissionais de saúde de forma remota, permitindo agendamento, realização e acompanhamento de consultas online. O sistema deve atender a pacientes, médicos e administradores, com foco em segurança, privacidade e conformidade com a LGPD e as normas de telemedicina vigentes no Brasil (CFM, Res. 2.314/2022 e atualizações).

**Observação sobre a referência em vídeo:** O vídeo indicado em `sistema.md` (YouTube) não é acessível por ferramentas automatizadas. As funcionalidades listadas a seguir foram derivadas da descrição do projeto, das hipóteses do documento `sistema.md` e de padrões reconhecidos em sistemas de telemedicina no contexto brasileiro. Qualquer funcionalidade que dependa de validação do vídeo está marcada como **[PENDENTE DE CONFIRMAÇÃO]**.

---

## 2. Premissas adotadas

| ID | Premissa | Status |
|---|---|---|
| P-001 | O sistema atende apenas ao contexto brasileiro (LGPD, CFM) | Premissa — confirmar |
| P-002 | MVP sem videochamada integrada (integração futura) | Premissa — confirmar |
| P-003 | Autenticação via e-mail e senha, com token (Sanctum) | Premissa — confirmar |
| P-004 | Pagamentos fora do escopo do MVP | Premissa — confirmar |
| P-005 | Prontuário eletrônico limitado a anotações de consulta no MVP | Premissa — confirmar |
| P-006 | Notificações por e-mail (push/SMS fora do MVP) | Premissa — confirmar |
| P-007 | Um médico pode atuar em mais de uma especialidade | Premissa — confirmar |
| P-008 | Pacientes se auto-cadastram; médicos são cadastrados pelo administrador | Premissa — confirmar |
| P-009 | O sistema terá um único tenant (sem multi-tenancy no MVP) | Premissa — confirmar |
| P-010 | Idioma da interface: português brasileiro | Premissa — fixo |

---

## 3. Atores identificados

| Ator | Descrição | Observações |
|---|---|---|
| **Administrador** | Gerencia usuários, médicos, especialidades e configurações do sistema | Acesso total |
| **Médico** | Gerencia disponibilidade, realiza consultas, registra anotações | Acesso restrito aos seus pacientes |
| **Paciente** | Agenda consultas, acompanha histórico, acessa resultados | Acesso restrito aos próprios dados |
| **Recepcionista** | [PENDENTE DE CONFIRMAÇÃO] Gerencia agendamentos em nome do paciente | Escopo a confirmar |
| **Sistema** | Ator interno — executa notificações, auditoria, jobs automáticos | — |

---

## 4. Dúvidas e decisões pendentes

| ID | Questão | Impacto | Urgência |
|---|---|---|---|
| D-001 | O sistema terá videochamada integrada no MVP? | Alto — impacto em arquitetura e infra | Alta |
| D-002 | Existe o perfil "Recepcionista"? | Médio — afeta fluxo de agendamento | Média |
| D-003 | Médico pode se auto-cadastrar ou somente admin cadastra? | Médio — afeta fluxo de onboarding | Alta |
| D-004 | Pagamento de consultas está no escopo? | Alto — módulo inteiro | Alta |
| D-005 | Prontuário eletrônico completo ou apenas anotações simples? | Alto — impacto em conformidade CFM | Alta |
| D-006 | Notificações: apenas e-mail ou também push/SMS? | Médio — afeta integrações externas | Média |
| D-007 | O sistema é multi-clínica (multi-tenant) ou monoclínica? | Alto — impacto em toda a arquitetura | Alta |
| D-008 | Laudos e exames são gerenciados pelo sistema? | Médio — escopo de prontuário | Média |
| D-009 | Existe integração com planos de saúde (operadoras)? | Alto — módulo de faturamento | Alta |
| D-010 | Qual é a política de cancelamento de consultas? | Médio — regras de negócio | Alta |
| D-011 | Médico pode bloquear horários específicos (férias, ausências)? | Médio — afeta disponibilidade | Média |
| D-012 | Consultas têm duração fixa ou variável por especialidade? | Médio — afeta agendamento | Média |
