# Проект "Заметки"

@../.specify/memory/constitution.md

---

# Development Guidelines

Auto-generated from all feature plans. Last updated: 2026-03-01

## Active Technologies
- PHP 8.4 (backend), TypeScript 5.9 / Node.js 20 LTS (frontend) + Symfony 8.0, Doctrine ORM 3.6 + DBAL (native SQL for FTS), nelmio/api-doc-bundle 5.9 (backend); Vue 3.5, Pinia 3.0, Vue Router 5.0, DOMPurify 3.3.x (frontend) (008-notes-fulltext-search)
- PostgreSQL 18.1 — tsvector column + GIN index + BEFORE trigger on `note` table (008-notes-fulltext-search)

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
- 008-notes-fulltext-search: Added PHP 8.4 (backend), TypeScript 5.9 / Node.js 20 LTS (frontend) + Symfony 8.0, Doctrine ORM 3.6 + DBAL (native SQL for FTS), nelmio/api-doc-bundle 5.9 (backend); Vue 3.5, Pinia 3.0, Vue Router 5.0, DOMPurify 3.3.x (frontend)
- 007-notes-favorites-trash: Added PostgreSQL 18.1 (Docker volume `postgres_data`)
- 006-notes-tags: Added PHP 8.4 (backend), TypeScript 5.9 / Node.js 20 LTS (frontend) + Symfony 8.0, Doctrine ORM 3.6, symfony/uid (backend); Vue 3.5, Pinia 3.0, Vue Router 5.0 (frontend)
- 005-notes-folders: Added PHP 8.4 (backend), TypeScript 5.9 / Node.js 20 LTS (frontend) + Symfony 8.0, Doctrine ORM 3.6, symfony/uid, nelmio/api-doc-bundle (backend); Vue 3.5, Vite 7.3, Vue Router 5.0, Pinia 3.0 (frontend)
- 004-notes-crud-markdown: Added PHP 8.4 (backend), TypeScript 5.9 / Node.js 20 LTS (frontend) + Symfony 8.0, Doctrine ORM 3.6, symfony/uid, nelmio/api-doc-bundle 5.9 (backend); Vue 3.5, Vite 7.3, Vue Router 5.0, Pinia 3.0, marked 17.x, DOMPurify 3.3.x (frontend)
- 002-frontend-init: Added TypeScript 5.9 / Node.js 20+ LTS + Vue 3.5, Vite 7.3, Vue Router 5.0, Pinia 3.0, Sass 1.97
- 001-backend-infra-init: Added PHP 8.4 + Symfony 8.* (skeleton), symfony/validator, symfony/serializer, friendsofphp/php-cs-fixer ^3.0, phpunit/phpunit ^13.0

<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
