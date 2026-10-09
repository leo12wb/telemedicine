# Requisitos Funcionais — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Proposta inicial — aguardando aprovação

> Legenda de prioridade: **MVP** = versão mínima utilizável | **V2** = próximas versões | **FUTURO** = versões futuras/a validar

---

## Módulo 1 — Autenticação e Autorização

### RF-001 — Registro de paciente
- **Nome:** Registro de paciente
- **Descrição:** O sistema deve permitir que um visitante se cadastre como paciente, fornecendo dados pessoais básicos.
- **Objetivo:** Permitir o acesso ao sistema por novos pacientes sem intervenção de um administrador.
- **Atores:** Visitante (futuro Paciente)
- **Pré-condições:** Usuário não autenticado; e-mail não cadastrado.
- **Fluxo principal:** 1. Visitante acessa a tela de registro. 2. Preenche nome, e-mail, senha e confirma senha. 3. Sistema valida os dados. 4. Sistema cria o usuário com o perfil `paciente`. 5. Sistema envia e-mail de verificação.
- **Fluxos alternativos:** E-mail já cadastrado → sistema informa o erro sem revelar dados.
- **Exceções:** Falha no envio de e-mail → conta criada, e-mail agendado para reenvio.
- **Regras de negócio:** RN-001, RN-002.
- **Dados envolvidos:** nome, e-mail, senha (hash bcrypt), CPF (opcional no MVP), data de nascimento (opcional).
- **Critérios de aceitação:** Conta criada com perfil correto; senha armazenada como hash; e-mail de verificação enviado; login não permitido antes da verificação.
- **Prioridade:** MVP
- **Dependências:** RF-003

---

### RF-002 — Login
- **Nome:** Login
- **Descrição:** Usuários autenticados (paciente, médico, administrador) acessam o sistema com e-mail e senha.
- **Objetivo:** Controlar o acesso ao sistema.
- **Atores:** Paciente, Médico, Administrador
- **Pré-condições:** Conta ativa e verificada.
- **Fluxo principal:** 1. Usuário informa e-mail e senha. 2. Sistema valida credenciais. 3. Sistema emite token de acesso (Sanctum). 4. Usuário é redirecionado para o painel do seu perfil.
- **Fluxos alternativos:** Credenciais inválidas → mensagem genérica de erro.
- **Exceções:** Conta bloqueada após N tentativas → retorna erro com instrução de desbloqueio.
- **Regras de negócio:** RN-001, RN-003.
- **Dados envolvidos:** e-mail, senha, token JWT/Sanctum.
- **Critérios de aceitação:** Token emitido somente para credenciais válidas; bloqueio após tentativas excessivas; redirecionamento correto por perfil.
- **Prioridade:** MVP
- **Dependências:** RF-001

---

### RF-003 — Verificação de e-mail
- **Nome:** Verificação de e-mail
- **Descrição:** Após o registro, o usuário recebe um e-mail com link de verificação.
- **Objetivo:** Garantir que o e-mail informado pertence ao usuário.
- **Atores:** Paciente, Médico
- **Prioridade:** MVP
- **Dependências:** RF-001

---

### RF-004 — Recuperação de senha
- **Nome:** Recuperação de senha
- **Descrição:** Usuário solicita redefinição de senha via e-mail.
- **Prioridade:** MVP
- **Dependências:** RF-002

---

### RF-005 — Logout
- **Nome:** Logout
- **Descrição:** Encerramento da sessão com revogação do token de acesso.
- **Prioridade:** MVP
- **Dependências:** RF-002

---

## Módulo 2 — Gerenciamento de Usuários e Perfis

### RF-006 — Gerenciamento de usuários pelo administrador
- **Nome:** Gerenciamento de usuários
- **Descrição:** O administrador pode listar, criar, editar, ativar, desativar e excluir usuários de qualquer perfil.
- **Atores:** Administrador
- **Prioridade:** MVP
- **Dependências:** RF-002

---

### RF-007 — Perfil do usuário
- **Nome:** Perfil do usuário
- **Descrição:** Cada usuário pode visualizar e editar seus dados pessoais (nome, e-mail, foto, senha).
- **Atores:** Paciente, Médico, Administrador
- **Prioridade:** MVP
- **Dependências:** RF-002

---

## Módulo 3 — Gerenciamento de Médicos

### RF-008 — Cadastro de médico
- **Nome:** Cadastro de médico
- **Descrição:** O administrador cadastra um médico informando dados pessoais, CRM, especialidades e dados de contato.
- **Atores:** Administrador
- **Pré-condições:** Usuário com perfil `medico` ainda não associado.
- **Fluxo principal:** 1. Admin acessa área de médicos. 2. Preenche formulário com nome, CRM, UF do CRM, especialidades, e-mail. 3. Sistema cria o usuário e o perfil do médico. 4. Sistema envia convite de acesso ao médico.
- **Regras de negócio:** RN-004, RN-005.
- **Dados envolvidos:** nome, CRM, UF do CRM, especialidade(s), e-mail, telefone, foto.
- **Critérios de aceitação:** CRM único por UF; médico pode ter mais de uma especialidade; médico recebe acesso ao sistema.
- **Prioridade:** MVP
- **Dependências:** RF-002, RF-010

---

### RF-009 — Edição e desativação de médico
- **Nome:** Edição e desativação de médico
- **Descrição:** O administrador pode editar dados do médico e desativar o acesso.
- **Atores:** Administrador
- **Regras de negócio:** RN-006.
- **Prioridade:** MVP
- **Dependências:** RF-008

---

## Módulo 4 — Gerenciamento de Pacientes

### RF-010 — Cadastro de paciente pelo administrador
- **Nome:** Cadastro manual de paciente
- **Descrição:** O administrador pode cadastrar um paciente diretamente.
- **Atores:** Administrador
- **Prioridade:** MVP
- **Dependências:** RF-002

---

### RF-011 — Perfil clínico do paciente
- **Nome:** Perfil clínico do paciente
- **Descrição:** O médico pode visualizar dados básicos do paciente (nome, data de nascimento, convênio) em uma consulta.
- **Atores:** Médico
- **Regras de negócio:** RN-007.
- **Prioridade:** MVP
- **Dependências:** RF-008

---

## Módulo 5 — Especialidades Médicas

### RF-012 — CRUD de especialidades
- **Nome:** Gerenciamento de especialidades
- **Descrição:** O administrador gerencia a lista de especialidades médicas disponíveis no sistema.
- **Atores:** Administrador
- **Dados envolvidos:** nome da especialidade, descrição, status (ativo/inativo).
- **Prioridade:** MVP
- **Dependências:** RF-002

---

## Módulo 6 — Disponibilidade Médica

### RF-013 — Configuração de horários de atendimento
- **Nome:** Configuração de disponibilidade
- **Descrição:** O médico configura seus dias e horários de atendimento recorrentes.
- **Atores:** Médico
- **Fluxo principal:** 1. Médico acessa configuração de agenda. 2. Seleciona dias da semana e horários de início/fim. 3. Define duração padrão da consulta. 4. Sistema salva os slots recorrentes.
- **Regras de negócio:** RN-008, RN-009.
- **Dados envolvidos:** dia da semana, horário início, horário fim, duração da consulta (minutos), status.
- **Critérios de aceitação:** Horários não se sobrepõem; slots gerados automaticamente conforme a duração.
- **Prioridade:** MVP
- **Dependências:** RF-008

---

### RF-014 — Bloqueio de horários
- **Nome:** Bloqueio de horários
- **Descrição:** O médico bloqueia datas/horários específicos (férias, ausências, feriados).
- **Atores:** Médico, Administrador
- **Regras de negócio:** RN-010.
- **Prioridade:** MVP
- **Dependências:** RF-013

---

## Módulo 7 — Agendamento de Consultas

### RF-015 — Busca de disponibilidade
- **Nome:** Busca de disponibilidade
- **Descrição:** O paciente busca médicos disponíveis por especialidade e data.
- **Atores:** Paciente
- **Prioridade:** MVP
- **Dependências:** RF-013

---

### RF-016 — Agendamento de consulta
- **Nome:** Agendamento de consulta
- **Descrição:** O paciente agenda uma consulta com um médico em um horário disponível.
- **Atores:** Paciente
- **Pré-condições:** Paciente autenticado; slot disponível; sem conflito de horário.
- **Fluxo principal:** 1. Paciente seleciona especialidade e médico. 2. Sistema exibe slots disponíveis. 3. Paciente seleciona data/hora. 4. Paciente confirma. 5. Sistema reserva o slot atomicamente. 6. Sistema envia confirmação por e-mail.
- **Regras de negócio:** RN-011, RN-012, RN-013.
- **Critérios de aceitação:** Sem double-booking; slot bloqueado atomicamente; e-mail enviado; consulta com status `agendada`.
- **Prioridade:** MVP
- **Dependências:** RF-015, RF-013

---

### RF-017 — Cancelamento de consulta
- **Nome:** Cancelamento de consulta
- **Descrição:** Paciente ou médico cancela uma consulta agendada.
- **Atores:** Paciente, Médico, Administrador
- **Regras de negócio:** RN-014, RN-015.
- **Prioridade:** MVP
- **Dependências:** RF-016

---

### RF-018 — Reagendamento de consulta
- **Nome:** Reagendamento
- **Descrição:** Paciente solicita reagendamento para outro horário disponível.
- **Atores:** Paciente
- **Regras de negócio:** RN-014, RN-015.
- **Prioridade:** MVP
- **Dependências:** RF-016, RF-017

---

## Módulo 8 — Realização da Consulta

### RF-019 — Início e encerramento de consulta
- **Nome:** Controle de status da consulta
- **Descrição:** O médico inicia e encerra a consulta, alterando o status correspondente.
- **Atores:** Médico
- **Regras de negócio:** RN-016.
- **Status possíveis:** `agendada` → `em_andamento` → `concluida` | `cancelada` | `paciente_ausente`
- **Prioridade:** MVP
- **Dependências:** RF-016

---

### RF-020 — Anotações clínicas da consulta
- **Nome:** Anotações clínicas
- **Descrição:** O médico registra anotações/observações durante ou após a consulta.
- **Atores:** Médico
- **Regras de negócio:** RN-007, RN-017.
- **Prioridade:** MVP
- **Dependências:** RF-019

---

## Módulo 9 — Histórico de Consultas

### RF-021 — Histórico do paciente
- **Nome:** Histórico de consultas do paciente
- **Descrição:** O paciente visualiza todas as suas consultas passadas e futuras.
- **Atores:** Paciente
- **Regras de negócio:** RN-007.
- **Prioridade:** MVP
- **Dependências:** RF-016

---

### RF-022 — Histórico do médico
- **Nome:** Agenda e histórico do médico
- **Descrição:** O médico visualiza sua agenda (consultas futuras) e histórico de atendimentos.
- **Atores:** Médico
- **Prioridade:** MVP
- **Dependências:** RF-016

---

## Módulo 10 — Painéis (Dashboards)

### RF-023 — Painel do administrador
- **Nome:** Dashboard do administrador
- **Descrição:** Visão geral com totais de médicos, pacientes, consultas por status, alertas.
- **Atores:** Administrador
- **Prioridade:** MVP
- **Dependências:** RF-008, RF-016

---

### RF-024 — Painel do médico
- **Nome:** Dashboard do médico
- **Descrição:** Próximas consultas do dia, resumo semanal, alertas de pacientes ausentes.
- **Atores:** Médico
- **Prioridade:** MVP
- **Dependências:** RF-016

---

### RF-025 — Painel do paciente
- **Nome:** Dashboard do paciente
- **Descrição:** Próximas consultas, histórico recente, acesso rápido ao agendamento.
- **Atores:** Paciente
- **Prioridade:** MVP
- **Dependências:** RF-016

---

## Módulo 11 — Notificações

### RF-026 — Notificação de confirmação de agendamento
- **Nome:** E-mail de confirmação
- **Descrição:** Envio automático de e-mail ao paciente e ao médico ao agendar uma consulta.
- **Atores:** Sistema
- **Prioridade:** MVP
- **Dependências:** RF-016

---

### RF-027 — Notificação de cancelamento
- **Nome:** E-mail de cancelamento
- **Descrição:** Envio automático de e-mail ao paciente e ao médico ao cancelar uma consulta.
- **Prioridade:** MVP
- **Dependências:** RF-017

---

### RF-028 — Lembrete de consulta [V2]
- **Nome:** Lembrete automático
- **Descrição:** Envio automático de lembrete 24h antes da consulta.
- **Prioridade:** V2
- **Dependências:** RF-016

---

## Módulo 12 — Auditoria [MVP básico]

### RF-029 — Registro de auditoria
- **Nome:** Log de auditoria
- **Descrição:** O sistema registra operações sensíveis: login, criação/alteração de consulta, acesso a dados clínicos, alteração de usuário.
- **Atores:** Sistema
- **Regras de negócio:** RN-018.
- **Prioridade:** MVP (básico) — auditoria completa em V2
- **Dependências:** RF-002

---

## Requisitos fora do MVP (V2 / FUTURO)

| ID | Nome | Prioridade |
|---|---|---|
| RF-030 | Videochamada integrada | FUTURO |
| RF-031 | Prontuário eletrônico completo (CFM) | V2 |
| RF-032 | Prescrição digital | FUTURO |
| RF-033 | Upload de exames e laudos | V2 |
| RF-034 | Integração com planos de saúde | FUTURO |
| RF-035 | Pagamento online | FUTURO |
| RF-036 | Notificações push / SMS | V2 |
| RF-037 | Relatórios e exportação | V2 |
| RF-038 | Perfil Recepcionista | V2 |
| RF-039 | Multi-tenancy (multi-clínica) | FUTURO |
