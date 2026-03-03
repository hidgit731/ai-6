# Проект "Заметки"

@../.specify/memory/constitution.md

---

# Development Guidelines

Auto-generated from all feature plans. Last updated: 2026-03-01

## Active Technologies
- PHP 8.4 (backend_monolith), TypeScript 5.9 / Node.js 20 LTS (frontend) + Docker Compose v2, Nginx 1.27-alpine, PostgreSQL 18.1-bookworm, PHP 8.4-fpm-alpine, Node 20-alpine (003-docker-infra)
- PostgreSQL 18 (именованный Docker volume для данных БД) (003-docker-infra)

## Project Structure

```text
backend_monolith/
frontend/
```

## Commands
```
# Backend (backend_monolith/)
# cd backend_monolith && composer install        — установка зависимостей
# cd backend_monolith && php bin/console         — Symfony CLI
# cd backend_monolith && php vendor/bin/phpunit  — запуск тестов
# cd backend_monolith && php vendor/bin/php-cs-fixer fix  — форматирование кода

# Frontend (frontend/)
# cd frontend && npm run dev        — dev server
# cd frontend && npm run build      — production build
# cd frontend && npm run type-check — TypeScript check
# cd frontend && npm test           — unit tests
```

## Code Style

PHP 8.4: Follow standard conventions
TypeScript 5.9 / Node.js 20+: Follow standard conventions; strict mode; `<script setup lang="ts">`

## Recent Changes

- 001-backend-infra-init: Added PHP 8.4 + Symfony 8.* (skeleton), symfony/validator, symfony/serializer, friendsofphp/php-cs-fixer ^3.0, phpunit/phpunit ^13.0
- 002-frontend-init: Added TypeScript 5.9 / Node.js 20+ LTS + Vue 3.5, Vite 7.3, Vue Router 5.0, Pinia 3.0, Sass 1.97
- 003-docker-infra: Added PHP 8.4 (backend_monolith), TypeScript 5.9 / Node.js 20 LTS (frontend) + Docker Compose v2, Nginx 1.27-alpine, PostgreSQL 18.1-bookworm, PHP 8.4-fpm-alpine, Node 20-alpine

<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
