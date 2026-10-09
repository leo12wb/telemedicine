# Docker e Infraestrutura — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Implementado (Fase 4)

---

## 1. Serviços Docker (compose.yaml)

| Serviço | Imagem base | Função |
|---|---|---|
| `app` | `php:8.2-fpm-alpine` | Laravel (PHP-FPM) |
| `nginx` | `nginx:1.25-alpine` | Proxy reverso + assets estáticos |
| `postgres` | `postgres:16-alpine` | Banco de dados principal |
| `queue` | Mesmo Dockerfile do `app` | Laravel queue worker (`queue:work`) |
| `mailpit` | `axllent/mailpit:latest` | Captura de e-mails em desenvolvimento |

---

## 2. Arquivos de infraestrutura

```
Dockerfile                        # Imagem PHP-FPM (app + queue)
compose.yaml                      # Orquestração Docker Compose
docker/
  nginx/default.conf              # Configuração Nginx (FastCGI + static assets)
  php/php.ini                     # Configuração PHP (produção / base)
  php/php-dev.ini                 # Sobreposição para desenvolvimento (OPcache off, erros on)
Makefile                          # Comandos de conveniência
.env.example                      # Template de variáveis de ambiente
```

---

## 3. Rede e volumes

### Rede
- Rede interna: `telemedicina_network` (bridge)
- PostgreSQL acessível apenas internamente (porta 5432 não exposta ao host)
- Nginx expõe `${APP_PORT:-8080}:80` ao host

### Volumes
| Volume | Uso |
|---|---|
| `postgres_data` | Dados persistentes do PostgreSQL |
| `app_storage` | `storage/app` do Laravel (uploads) |
| `.` (bind mount) | Código-fonte montado em desenvolvimento |
| `docker/php/php-dev.ini` | PHP dev overrides (OPcache desabilitado) |

---

## 4. Dockerfile

- Base: `php:8.2-fpm-alpine`
- Extensões instaladas: `pdo_pgsql`, `pgsql`, `gd`, `zip`, `bcmath`, `exif`, `pcntl`, `mbstring`, `intl`, `opcache`
- Composer 2.8 incluído via multi-stage copy
- Usuário não-root: `appuser:appgroup` (UID/GID 1000)
- PHP ini de produção copiado para `/usr/local/etc/php/conf.d/app.ini`
- Em desenvolvimento, `docker/php/php-dev.ini` é montado como `app-dev.ini` (sobrepõe settings de produção)

---

## 5. Primeira execução (setup completo)

```bash
# Clonar o repositório
git clone <repo> && cd telemedicina

# Configurar e inicializar tudo de uma vez
make setup
# Equivalente a:
#   cp .env.example .env
#   docker compose build
#   docker compose up -d
#   docker compose exec app php artisan key:generate
#   docker compose exec app php artisan migrate
#   docker compose exec app php artisan storage:link
#   npm ci && npm run build

# Acesse: http://localhost:8080
```

---

## 6. Comandos do dia a dia (via Makefile)

```bash
make up            # Sobe os containers
make down          # Para os containers
make shell         # Shell no container app
make migrate       # php artisan migrate
make migrate-fresh # migrate:fresh --seed
make logs          # Logs de todos os serviços
make logs-app      # Logs do PHP-FPM
make test-backend  # php artisan test (dentro do Docker)
make test-local    # php artisan test (local, SQLite)
make test-frontend # npx vitest run
make dev           # npm run dev (Vite local)
make build-fe      # npm run build
```

---

## 7. Healthchecks

| Serviço | Verificação |
|---|---|
| `postgres` | `pg_isready -U ${DB_USERNAME} -d ${DB_DATABASE}` (interval 5s, retries 10) |
| `app` | Depende de `postgres` com `condition: service_healthy` |
| `queue` | Depende de `postgres` com `condition: service_healthy` |
| `nginx` | Depende de `app` (start order) |

---

## 8. Notas de segurança

- Container `app` e `queue` executam como `appuser` (não-root)
- PostgreSQL sem senha padrão — obrigatório definir `DB_PASSWORD` no `.env`
- Nginx aponta apenas para `public/` do Laravel
- Nginx nega acesso direto a `storage/`, `bootstrap/`, `config/`, `routes/`, `app/`, `resources/`, `tests/`, `vendor/`
- Nginx bloqueia acesso a `.env`, `.git`, `.ht*`
- Nenhuma credencial no `Dockerfile` ou `compose.yaml` — tudo via variável de ambiente
- Versões de imagens fixadas (não `:latest` exceto `mailpit` que é ferramenta de dev)
- `expose_php = Off` no `php.ini` de produção

---

## 9. Variáveis de ambiente obrigatórias

| Variável | Descrição |
|---|---|
| `APP_KEY` | Gerada por `php artisan key:generate` |
| `DB_PASSWORD` | Senha do PostgreSQL |

Todas as demais têm valores padrão seguros para desenvolvimento no `.env.example`.
