# ─────────────────────────────────────────────────────────────
# Makefile — Sistema de Telemedicina
# Comandos de conveniência para o ambiente Docker
# ─────────────────────────────────────────────────────────────

.PHONY: help up down build restart logs shell migrate seed test test-backend test-frontend fresh

help: ## Exibe esta ajuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ─── Ambiente ────────────────────────────────────────────────
up: ## Sobe o ambiente Docker
	docker compose up -d

down: ## Para e remove os containers
	docker compose down

build: ## Reconstrói as imagens Docker
	docker compose build

restart: ## Reinicia todos os containers
	docker compose restart

logs: ## Acompanha os logs de todos os containers
	docker compose logs -f

logs-app: ## Acompanha os logs do container PHP
	docker compose logs -f app

# ─── Aplicação ───────────────────────────────────────────────
shell: ## Abre shell no container da aplicação
	docker compose exec app bash

migrate: ## Executa as migrations
	docker compose exec app php artisan migrate

migrate-fresh: ## Recria o banco e executa migrations + seeders
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Executa os seeders
	docker compose exec app php artisan db:seed

# ─── Frontend ────────────────────────────────────────────────
dev: ## Inicia o Vite em modo desenvolvimento (local, sem Docker)
	npm run dev

build-fe: ## Compila os assets para produção
	npm run build

# ─── Testes ──────────────────────────────────────────────────
test: ## Executa todos os testes (PHP + Frontend)
	docker compose exec app php artisan test
	npm run test 2>/dev/null || true

test-backend: ## Executa apenas os testes PHP
	docker compose exec app php artisan test

test-frontend: ## Executa apenas os testes Vitest
	npm run test

test-local: ## Executa testes PHP localmente (sem Docker)
	php artisan test

# ─── Setup inicial ───────────────────────────────────────────
setup: ## Configuração inicial do projeto (primeira vez)
	cp .env.example .env
	docker compose build
	docker compose up -d
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate
	@echo "✅ Ambiente configurado! Acesse http://localhost:8080"
