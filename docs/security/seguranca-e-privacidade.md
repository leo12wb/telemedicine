# Segurança e Privacidade — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Proposta inicial — aguardando aprovação

---

## 1. Autenticação

- **Laravel Sanctum** com tokens de acesso pessoal (stateless).
- Senhas armazenadas com **bcrypt** (custo ≥ 12).
- Tokens com expiração configurável via `.env` (`SANCTUM_TOKEN_EXPIRATION`).
- **Bloqueio por tentativas**: após 5 tentativas falhadas de login, bloqueio temporário de 15 minutos (valores a confirmar).
- Verificação de e-mail obrigatória antes de acessar recursos protegidos.
- Recuperação de senha via token de uso único com expiração de 60 minutos.

---

## 2. Autorização

- **RBAC simples** com três perfis: `admin`, `medico`, `paciente`.
- **Laravel Policies** por recurso (ex: `AppointmentPolicy`, `ClinicalNotePolicy`).
- Regra de **ownership**: cada usuário só acessa seus próprios recursos.
- Regra de **relacionamento médico-paciente**: médico acessa dados clínicos apenas de pacientes com quem tem/teve consulta.
- Nenhum dado clínico acessível por admin (apenas dados de gestão).

---

## 3. Proteção contra ataques

| Ameaça | Mitigação |
|---|---|
| SQL Injection | Eloquent + Query Builder com bindings parametrizados. SQL raw proibido sem justificativa. |
| XSS | Sanitização de saída via API Resources (JSON). Frontend Vue escapa HTML por padrão. |
| CSRF | Não aplicável em API stateless com token Bearer. Endpoints web (se houver) usam proteção CSRF do Laravel. |
| Mass Assignment | `$fillable` explícito em todos os Models. Nunca usar `$guarded = []` sem revisão. |
| IDOR | Verificação de ownership em todas as Policies. IDs são UUIDs (não enumeráveis). |
| Brute Force | Rate Limiting no endpoint de login (5 tentativas/15min por IP). |
| Enumeração de e-mail | Respostas genéricas em erros de autenticação e recuperação de senha. |

---

## 4. Dados sensíveis

- **Dados clínicos** (anotações, diagnósticos): acesso restrito ao médico da consulta e ao paciente (leitura própria).
- **Senhas**: nunca logadas, nunca retornadas na API.
- **Tokens**: nunca logados.
- **CPF**: quando coletado, não exibido em listagens.
- **Logs**: sem dados clínicos, sem senhas, sem tokens.

---

## 5. LGPD — pontos de atenção

> Estes itens **não são atendidos apenas pela implementação técnica**. Requerem validação jurídica e organizacional.

| Item | Status técnico | Status organizacional |
|---|---|---|
| Base legal para coleta de dados de saúde | Implementação pendente | **[PENDENTE DE VALIDAÇÃO JURÍDICA]** |
| Política de privacidade acessível ao usuário | A implementar (tela/link) | **[PENDENTE]** |
| Consentimento informado do paciente | A implementar | **[PENDENTE]** |
| Direito de acesso e portabilidade | A implementar (V2) | **[PENDENTE]** |
| Direito de exclusão ("direito ao esquecimento") | Impacto no histórico clínico — análise necessária | **[PENDENTE JURÍDICO]** |
| DPO (Data Protection Officer) designado | N/A (técnico) | **[PENDENTE ORGANIZACIONAL]** |
| Política de retenção e descarte | A definir | **[PENDENTE]** |

---

## 6. Infraestrutura segura

- PostgreSQL **não exposto** publicamente (rede interna Docker).
- Segredos exclusivamente em **variáveis de ambiente** (`.env`). Arquivo `.env` no `.gitignore`.
- Container da aplicação executando com **usuário não-root**.
- HTTPS obrigatório em produção (configuração Nginx com TLS).
- Separação de variáveis por ambiente: `.env.example` no repositório, `.env` local/produção fora.

---

## 7. Auditoria

Eventos que geram registro em `audit_logs`:

- Login e logout
- Criação, edição e desativação de usuário
- Agendamento, cancelamento e reagendamento de consulta
- Início e encerramento de consulta
- Acesso a dados clínicos (visualização de anotações)
- Criação e edição de anotações clínicas
- Alteração de disponibilidade do médico
- Bloqueio de horários
- Operações administrativas críticas

Registros de auditoria são **imutáveis** (sem update/delete pelo sistema).

---

## 8. Conformidade regulatória — telemedicina

- O sistema deve observar as normas do **CFM (Resolução 2.314/2022)** e atualizações.
- Aspectos regulatórios que **requerem responsável médico/jurídico qualificado**:
  - Limites do diagnóstico por telemedicina.
  - Requisitos de identificação de médico e paciente.
  - Sigilo médico e compartilhamento de prontuário.
  - Prescrição digital.
- **Não declare conformidade regulatória baseada apenas na implementação técnica.**
