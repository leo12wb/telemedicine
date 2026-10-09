# Requisitos Não Funcionais — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Proposta inicial — aguardando aprovação

> Valores numéricos marcados com `[META]` são metas sugeridas, não requisitos aprovados.
> Valores marcados com `[APROVADO]` são requisitos fixos.

---

## Segurança

### RNF-001 — Autenticação segura
- **Descrição:** Autenticação via token (Laravel Sanctum). Senhas armazenadas com bcrypt (custo ≥ 12). Tokens com expiração configurável.
- **Métrica:** 100% das rotas protegidas exigem token válido [APROVADO].
- **Dependências:** RF-002

### RNF-002 — Autorização por políticas
- **Descrição:** Controle de acesso baseado em perfil (RBAC) com Laravel Policies e Gates. Cada recurso protegido individualmente.
- **Métrica:** Nenhum endpoint sensível acessível por perfil não autorizado [APROVADO].

### RNF-003 — Proteção contra ataques web
- **Descrição:** Proteção contra SQL Injection (Eloquent/Query Builder parametrizado), XSS (sanitização de saída), CSRF (tokens Laravel), Mass Assignment (fillable/guarded), Rate Limiting em endpoints sensíveis.
- **Métrica:** Nenhuma vulnerabilidade OWASP Top 10 nas rotas da API [META].

### RNF-004 — Gerenciamento seguro de credenciais
- **Descrição:** Segredos (chaves de API, credenciais de banco) exclusivamente em variáveis de ambiente. Nenhum segredo no repositório.
- **Métrica:** Zero secrets no histórico Git [APROVADO].

### RNF-005 — Limitação de tentativas (Rate Limiting)
- **Descrição:** Máximo de 5 tentativas de login por IP em janela de 15 minutos. Endpoints de API com throttle configurado.
- **Métrica:** Bloqueio efetivo após 5 tentativas [META — confirmar valor].

### RNF-006 — HTTPS obrigatório em produção
- **Descrição:** Toda comunicação em produção via HTTPS/TLS 1.2+.
- **Métrica:** Sem tráfego HTTP em produção [APROVADO].

---

## Privacidade e LGPD

### RNF-007 — Conformidade com a LGPD
- **Descrição:** Dados pessoais e de saúde tratados conforme a Lei 13.709/2018. Necessário validação jurídica para definição de base legal, retenção e descarte.
- **Nota:** Este requisito não é atendido apenas pela implementação técnica. Requer política de privacidade, DPO designado e processos organizacionais. **[PENDENTE DE VALIDAÇÃO JURÍDICA]**

### RNF-008 — Isolamento de dados entre pacientes
- **Descrição:** Um paciente não pode acessar dados de outro paciente, inclusive por manipulação de IDs em URLs ou parâmetros.
- **Métrica:** 100% das rotas de paciente verificam ownership [APROVADO].

### RNF-009 — Proteção de dados clínicos em logs
- **Descrição:** Logs do sistema não devem conter dados clínicos, senhas, tokens ou CPF em texto claro.
- **Métrica:** Zero dados sensíveis em arquivos de log [APROVADO].

### RNF-010 — Política de retenção de dados
- **Descrição:** Definir período de retenção para dados de consulta, anotações clínicas e logs de auditoria. **[PENDENTE DE DECISÃO]**

---

## Desempenho

### RNF-011 — Tempo de resposta da API
- **Descrição:** Endpoints principais respondem em até 500ms em condições normais de carga.
- **Métrica:** p95 ≤ 500ms com até 100 usuários simultâneos [META].

### RNF-012 — Tempo de carregamento do frontend
- **Descrição:** Primeiro carregamento significativo (FCP) em até 3 segundos em conexão banda larga.
- **Métrica:** FCP ≤ 3s [META].

### RNF-013 — Paginação obrigatória em listas
- **Descrição:** Toda resposta de lista com potencial de crescimento deve ser paginada.
- **Métrica:** Listas sem limit/pagination bloqueadas na revisão de código [APROVADO].

---

## Disponibilidade

### RNF-014 — Disponibilidade do serviço
- **Descrição:** Sistema disponível em horário comercial (07h–22h, segunda a sábado).
- **Métrica:** Uptime ≥ 99% no horário definido [META — referente a ambiente de produção futuro].

### RNF-015 — Recuperação de falhas
- **Descrição:** Em caso de falha do servidor, dados persistentes não devem ser perdidos. Jobs em fila devem ser reprocessáveis.
- **Métrica:** RPO (Recovery Point Objective) ≤ 24h [META].

---

## Manutenibilidade

### RNF-016 — Arquitetura em camadas
- **Descrição:** Separação obrigatória Controller / Service / Repository / Model conforme definido em `sistema.md`.
- **Nota:** Validado na revisão de código [APROVADO].

### RNF-017 — Cobertura de testes
- **Descrição:** Cada módulo do MVP deve ter cobertura de testes nas camadas Service e API (feature tests).
- **Métrica:** Cobertura ≥ 80% dos Services e Controllers do MVP [META].

### RNF-018 — Código padronizado
- **Descrição:** PSR-12 para PHP. ESLint + TypeScript para Vue. Formatação verificada em pipeline.
- **Métrica:** Zero erros de lint no pipeline [META].

---

## Escalabilidade

### RNF-019 — Arquitetura preparada para escala horizontal
- **Descrição:** A aplicação não deve depender de estado local (sessão em memória, arquivos locais). Sessões e filas externalizadas.
- **Nota:** Relevante para evolução futura, não exigido no MVP.

---

## Observabilidade

### RNF-020 — Logs estruturados
- **Descrição:** Logs da aplicação em formato estruturado (JSON) com nível, timestamp, contexto e identificador de requisição.
- **Prioridade:** V2 (MVP usa logs padrão do Laravel).

### RNF-021 — Auditoria de operações sensíveis
- **Descrição:** Registro imutável de ações sensíveis: autenticação, acesso a dados clínicos, criação/alteração de consultas, alteração de usuários.
- **Prioridade:** MVP (básico) — ver RF-029.

---

## Compatibilidade

### RNF-022 — Navegadores suportados
- **Descrição:** Chrome, Firefox, Edge (últimas 2 versões). Safari (última versão).
- **Nota:** Sem suporte a Internet Explorer [APROVADO].

### RNF-023 — Responsividade
- **Descrição:** Interface responsiva para desktop (≥ 1024px) e mobile (≥ 375px).
- **Métrica:** Sem quebra de layout nos breakpoints definidos [META].

---

## Testabilidade

### RNF-024 — Ambiente de testes isolado
- **Descrição:** Testes automatizados executados em banco de dados separado (SQLite em memória ou PostgreSQL de teste). Sem impacto em dados de desenvolvimento.

### RNF-025 — Dados de teste sintéticos
- **Descrição:** Factories e seeders usam apenas dados fictícios. Dados reais de pacientes nunca utilizados em testes [APROVADO].

---

## Backup

### RNF-026 — Estratégia de backup
- **Descrição:** Backup diário do banco de dados PostgreSQL. Retenção mínima de 30 dias. **[PENDENTE DE DECISÃO — ambiente de produção]**

---

## Acessibilidade

### RNF-027 — Acessibilidade básica
- **Descrição:** Interfaces com contraste adequado, navegação por teclado e atributos ARIA básicos.
- **Métrica:** Conformidade com WCAG 2.1 nível AA nos componentes principais [META — confirmar escopo].
