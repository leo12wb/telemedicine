# Sistema de Telemedicina

Plataforma web para gestão de consultas médicas online. Permite que clínicas e consultórios administrem médicos, pacientes, agendamentos e realizem consultas por videoconferência.

## Funcionalidades

- **Autenticação** — cadastro, login, recuperação de senha (Sanctum)
- **Usuários** — gerenciamento de contas com papéis: admin, médico e paciente
- **Médicos** — cadastro com CRM, especialidades, foto de perfil e agenda
- **Pacientes** — perfil clínico com histórico de consultas
- **Agendamentos** — criação, cancelamento, reagendamento e controle de status (agendada → em andamento → concluída)
- **Disponibilidade** — horários recorrentes e bloqueios por médico; busca de slots disponíveis
- **Videoconferência** — integração com Jitsi ou Daily.co, configurável pelo administrador
- **Dashboard** — resumo de consultas e indicadores básicos

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2, Laravel 12 |
| Frontend | Vue 3, TypeScript, Vite, Pinia |
| Banco de dados | PostgreSQL 16 |
| Autenticação | Laravel Sanctum |
| Estilo | Tailwind CSS |
| Testes backend | Pest (PHPUnit) |
| Testes frontend | Vitest |
| Container | Docker + Docker Compose |

## Pré-requisitos

- Docker e Docker Compose
- Node.js 20+ (apenas para desenvolvimento local do frontend)

## Instalação

```bash
# Clone o repositório
git clone <url-do-repositorio>
cd telemedicina

# Copie e configure o .env
cp .env.example .env
# Edite .env com DB_PASSWORD e demais variáveis necessárias

# Suba o ambiente completo
make setup
```

O comando `make setup` realiza: build das imagens, subida dos containers, instalação de dependências PHP e Node, geração da APP_KEY, execução das migrations e seeders, e build dos assets.

## Desenvolvimento

```bash
# Subir os containers
make up

# Vite em modo watch (hot-reload)
make dev

# Acessar shell do container PHP
make shell

# Recriar banco com dados de exemplo
make migrate-fresh
```

A aplicação fica disponível em `http://localhost` após subir o ambiente.

### Contas de desenvolvimento (após seed)

| E-mail | Senha | Papel |
|---|---|---|
| admin@telemedicina.local | password | Administrador |
| medico@telemedicina.local | password | Médico |
| paciente@telemedicina.local | password | Paciente |

## Testes

```bash
# Todos os testes
make test

# Apenas backend (Pest)
make test-backend

# Apenas frontend (Vitest)
make test-frontend

# Executar localmente sem Docker
php artisan test
npx vitest run
```

## Videoconferência

O provedor de videoconferência é configurado pelo administrador em **Configurações → Provedor**:

- **Nenhum** — videoconferência desativada
- **Jitsi** — use `https://meet.jit.si` (servidor público) ou a URL do seu servidor auto-hospedado
- **Daily.co** — requer chave de API e subdomínio da conta Daily.co

## Estrutura do projeto

```
app/
  Http/Controllers/Api/   # Controllers da API REST
  Models/                 # Eloquent models
  Services/               # Lógica de negócio
  Policies/               # Autorização por modelo
  Enums/                  # AppointmentStatus, UserRole
resources/js/
  pages/                  # Páginas Vue
  stores/                 # Estado global (Pinia)
  services/               # Chamadas à API
  types/                  # Interfaces TypeScript
routes/
  api.php                 # Rotas da API (/api/v1/...)
tests/
  Feature/                # Testes de integração por módulo
```

## Licença

MIT
