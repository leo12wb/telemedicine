# Regras de Negócio — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Implementado

> Regras marcadas com **[PENDENTE]** precisam de confirmação antes da implementação.

---

## Cadastro e Autenticação

### RN-001 — Unicidade de e-mail
- **Descrição:** Cada e-mail só pode estar associado a um único usuário no sistema, independentemente do perfil.
- **Impacto:** Validação na criação e edição de usuários.
- **Status:** Definido.

### RN-002 — Verificação de e-mail obrigatória
- **Descrição:** Um usuário recém-cadastrado não pode agendar consultas nem realizar atendimentos enquanto seu e-mail não estiver verificado.
- **Impacto:** Middleware de verificação nas rotas protegidas.
- **Status:** Definido.

### RN-003 — Bloqueio por tentativas de login
- **Descrição:** Após 5 tentativas de login malsucedidas, a conta é temporariamente bloqueada por 15 minutos. O valor exato é uma premissa — confirmar.
- **Impacto:** Rate Limiting + campo de bloqueio no usuário.
- **Status:** [PENDENTE — confirmar número de tentativas e duração do bloqueio].

---

## Médicos

### RN-004 — Unicidade do CRM por UF
- **Descrição:** A combinação CRM + UF deve ser única no sistema. Não podem existir dois médicos com o mesmo número de CRM na mesma UF.
- **Impacto:** Constraint única na tabela de médicos.
- **Status:** Definido.

### RN-005 — Médico pode ter mais de uma especialidade
- **Descrição:** Um médico pode estar associado a uma ou mais especialidades. A lista de especialidades é gerenciada pelo administrador.
- **Impacto:** Relacionamento N:N entre médico e especialidade.
- **Status:** Definido (premissa P-007).

### RN-006 — Desativação de médico com consultas futuras
- **Descrição:** Ao desativar um médico que possui consultas futuras agendadas, o sistema deve alertar o administrador e exigir confirmação. As consultas futuras devem ser canceladas automaticamente (com notificação aos pacientes) ou realocadas manualmente. **[PENDENTE — confirmar comportamento preferido]**
- **Status:** [PENDENTE].

---

## Disponibilidade

### RN-007 — Acesso a dados clínicos restrito ao relacionamento
- **Descrição:** Um médico só pode acessar dados clínicos (anotações, histórico) de pacientes que tiveram ou têm consulta agendada com ele. O administrador acessa para fins de gestão, sem visualizar conteúdo clínico.
- **Impacto:** Policy no acesso a consultas e anotações.
- **Status:** Definido.

### RN-008 — Horários de atendimento sem sobreposição
- **Descrição:** Um médico não pode ter dois horários de atendimento sobrepostos no mesmo dia e hora.
- **Impacto:** Validação ao salvar slots de disponibilidade.
- **Status:** Definido.

### RN-009 — Duração mínima e máxima de consulta
- **Descrição:** A duração de um slot de consulta deve ser de no mínimo 15 minutos e no máximo 120 minutos. **[PENDENTE — confirmar valores]**
- **Status:** [PENDENTE].

### RN-010 — Bloqueio de horário com consulta agendada
- **Descrição:** Um horário com consulta já agendada não pode ser bloqueado pelo médico sem o cancelamento prévio da consulta (com notificação ao paciente).
- **Status:** Definido.

---

## Agendamento

### RN-011 — Agendamento apenas em slots disponíveis
- **Descrição:** Um paciente só pode agendar uma consulta em um horário configurado pelo médico como disponível e que não esteja ocupado, bloqueado ou passado.
- **Status:** Definido.

### RN-012 — Prevenção de double-booking (concorrência)
- **Descrição:** O sistema deve garantir que dois pacientes não consigam agendar o mesmo slot simultaneamente. A reserva deve ser feita com lock otimista (ou pessimista) no banco de dados para evitar race conditions.
- **Impacto:** Estratégia de concorrência no PostgreSQL (SELECT FOR UPDATE ou constraint única).
- **Status:** Definido.

### RN-013 — Antecedência mínima para agendamento
- **Descrição:** Um paciente não pode agendar uma consulta com menos de X horas de antecedência. **[PENDENTE — confirmar valor: sugestão 2 horas]**
- **Status:** [PENDENTE].

### RN-014 — Prazo para cancelamento pelo paciente
- **Descrição:** O paciente pode cancelar uma consulta até Y horas antes do horário marcado. Após esse prazo, o cancelamento só pode ser feito pelo médico ou administrador. **[PENDENTE — confirmar valor: sugestão 24 horas]**
- **Status:** [PENDENTE].

### RN-015 — Status de consulta e transições permitidas
- **Descrição:** Uma consulta segue o fluxo de status:
  - `agendada` → `em_andamento` (pelo médico no horário)
  - `agendada` → `cancelada` (por paciente, médico ou admin)
  - `em_andamento` → `concluida` (pelo médico)
  - `em_andamento` → `paciente_ausente` (pelo médico)
  - `agendada` → `reagendada` (transição que cancela e cria nova consulta)
  - Consultas `concluidas` ou `canceladas` não podem ser alteradas.
- **Status:** Definido (fluxo base — confirmar se faltam estados).

### RN-016 — Início de consulta somente no horário
- **Descrição:** O médico só pode iniciar uma consulta (mudar para `em_andamento`) dentro de uma janela de tolerância antes/depois do horário agendado. **[PENDENTE — confirmar tolerância: sugestão ±10 minutos]**
- **Status:** [PENDENTE].

---

## Dados Clínicos

### RN-017 — Anotações clínicas imutáveis após encerramento
- **Descrição:** Após o encerramento da consulta, as anotações clínicas não podem ser editadas nem excluídas, apenas visualizadas. Alterações só pelo administrador com justificativa registrada. **[PENDENTE — confirmar se edição com auditoria é aceitável]**
- **Status:** [PENDENTE].

---

## Auditoria

### RN-018 — Operações que exigem auditoria
- **Descrição:** Os seguintes eventos devem ser registrados no log de auditoria: login e logout, criação/edição/desativação de usuário, agendamento/cancelamento/reagendamento de consulta, acesso a dados clínicos, início/encerramento de consulta, alteração de disponibilidade do médico, operações administrativas.
- **Status:** Definido (escopo mínimo — pode ser expandido).

---

## Conformidade

### RN-019 — Telemedicina conforme CFM
- **Descrição:** Funcionalidades de atendimento remoto devem seguir as normas do CFM (Resolução 2.314/2022 ou vigente). Em especial: identificação do médico e do paciente, sigilo médico, vedação de diagnóstico por imagem sem exame presencial anterior (quando aplicável). **[PENDENTE DE VALIDAÇÃO JURÍDICA/MÉDICA]**
- **Status:** [PENDENTE — fora do escopo técnico, requer responsável qualificado].
