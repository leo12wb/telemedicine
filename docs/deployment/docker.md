# Docker e Infraestrutura — Sistema de Telemedicina

**Data:** 2026-10-09
**Status:** Proposta — será implementada na Fase 4 (após aprovação do planejamento)

---

## 1. Serviços Docker (compose.yaml)

| Serviço | Imagem base | Função |
|---|---|---|
| `app` | `php:8.2-fpm-alpine` | Laravel (PHP-FPM) |
| `nginx` | `nginx:1.25-alpine` | Proxy reverso + assets estáticos |
| `postgres` | `postgres:16-alpine` | Banco de dados principal |
| `queue` | Mesmo Dockerfile do `app` | Laravel queue worker |
| `mailpit` | `axllent/mailpit` | Captura de e-mails em desenvolvimento |

---

## 2. Configuração planejada

### Rede
- Rede interna Docker: `telemedicina_network`
- PostgreSQL acessível apenas internamente (sem bind de porta para o host em produção)
- Nginx expõe porta 80 (dev) / 443 (prod)

### Volumes
- `postgres_data` — dados persistentes do PostgreSQL
- `app_storage` — storage do Laravel (uploads, logs)
- Código fonte montado como volume em desenvolvimento

### Variáveis de ambiente
- Todas as configurações sensíveis via `.env`
- `.env.example` versionado no repositório
- `.env` nunca commitado

---

## 3. Dockerfile (planejado — app)

```dockerfile
# Exemplo de estrutura — não implementado ainda
FROM php:8.2-fpm-alpine

# Instalar extensões PHP necessárias:
# pdo_pgsql, pgsql, zip, gd, exif, pcntl, bcmath, redis (opcional)

# Instalar Composer
# Copiar código da aplicação
# Instalar dependências PHP (composer install --no-dev --optimize-autoloader)
# Configurar permissões (storage, bootstrap/cache)
# Usuário não-root para execução

EXPOSE 9000
CMD ["php-fpm"]
```

---

## 4. Comandos de inicialização (planejados)

```bash
# Subir o ambiente de desenvolvimento
docker compose up -d

# Instalar dependências PHP
docker compose exec app composer install

# Instalar dependências Node (frontend)
docker compose exec app npm install

# Compilar assets frontend
docker compose exec app npm run dev

# Criar banco e executar migrations
docker compose exec app php artisan migrate

# Popular com dados de desenvolvimento
docker compose exec app php artisan db:seed

# Executar testes
docker compose exec app php artisan test

# Acompanhar logs
docker compose logs -f app
docker compose logs -f nginx
```

---

## 5. Healthchecks (planejados)

- `postgres`: `pg_isready -U ${DB_USERNAME}`
- `app`: verificação de arquivo ou endpoint `/api/health`
- `nginx`: dependência do `app` estar saudável

---

## 6. Notas de segurança da infraestrutura

- Container `app` executando como usuário não-root
- PostgreSQL sem senha padrão — senha definida via `.env`
- Nginx configurado para apontar para `public/` do Laravel
- Sem credenciais no `Dockerfile` ou `compose.yaml`
- Versões de imagem fixadas (não usar `:latest` em produção)
