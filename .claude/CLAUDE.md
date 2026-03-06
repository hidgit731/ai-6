# Проект "Заметки"

Монорепозиторий: REST API на Symfony 8 + SPA на Vue 3. Все команды выполняются внутри Docker-контейнеров через `docker compose exec`.

## Команды

### Backend (`backend_monolith/`)

```bash
# Зависимости
docker compose exec backend_monolith composer install

# Все тесты
docker compose exec backend_monolith php vendor/bin/phpunit

# Один тест-файл
docker compose exec backend_monolith php vendor/bin/phpunit tests/Unit/Application/Service/NoteServiceCreateTest.php

# Тест по имени метода
docker compose exec backend_monolith php vendor/bin/phpunit --filter testCreateNote

# Только unit-тесты
docker compose exec backend_monolith php vendor/bin/phpunit tests/Unit

# Только integration-тесты
docker compose exec backend_monolith php vendor/bin/phpunit tests/Integration

# Линтер
docker compose exec backend_monolith php vendor/bin/php-cs-fixer fix

# Symfony CLI
docker compose exec backend_monolith php bin/console <command>

# Миграции
docker compose exec backend_monolith php bin/console doctrine:migrations:migrate --no-interaction

# Фикстуры (демо-данные)
docker compose exec backend_monolith php bin/console doctrine:fixtures:load --no-interaction
```

**Первый запуск integration-тестов** требует создания тестовой БД:
```bash
docker compose exec backend_monolith php bin/console --env=test doctrine:database:create
docker compose exec backend_monolith php bin/console --env=test doctrine:migrations:migrate --no-interaction
```

### Frontend (`frontend/`)

```bash
# Все тесты
docker compose exec frontend npm test

# Один тест-файл
docker compose exec frontend npm test -- src/composables/useNotes.test.ts

# Тесты по шаблону
docker compose exec frontend npm test -- --grep "downloadMarkdown"

# TypeScript-проверка
docker compose exec frontend npm run type-check

# Production-сборка
docker compose exec frontend npm run build
```

## Архитектура Backend

Строгая слоистая архитектура. Зависимости направлены только внутрь: `Presentation → Application → Domain`. Слой `Infrastructure` реализует интерфейсы из `Domain`.

```
src/
├── Domain/           — сущности (Doctrine entities) + интерфейсы репозиториев (без реализаций)
├── Application/
│   ├── Service/      — вся бизнес-логика (единственное место)
│   └── DTO/
│       ├── Request/  — входящие данные (валидируются через Symfony Validator)
│       └── Response/ — исходящие данные (сериализуются в JsonResponse)
├── Infrastructure/
│   └── Persistence/Repository/  — Doctrine ORM реализации репозиториев
└── Presentation/HTTP/           — Action-контроллеры (один класс = __invoke())
```

**Поток запроса**: HTTP → `Action` (валидация Request DTO) → `Service` (бизнес-логика) → `RepositoryInterface` → `DoctrineRepository` → PostgreSQL → Response DTO → `JsonResponse`.

**Action-контроллер** — отдельный класс, атрибут `#[AsController]`, без наследования от `AbstractController`, без бизнес-логики:
```php
#[AsController]
#[Route('/api/notes', methods: ['POST'])]
class CreateNoteAction {
    public function __invoke(Request $request): JsonResponse { ... }
}
```

**Сервис** зависит только от интерфейсов, не знает о HTTP:
```php
final class NoteService {
    public function __construct(
        private readonly NoteRepositoryInterface $noteRepository,  // НЕ DoctrineNoteRepository
        // ...
    ) {}
}
```

**Конфликты маршрутов**: при похожих URL используй `priority: 1` для специфичных маршрутов перед общими (например `GET /api/notes/favorites` перед `GET /api/notes/{id}`).

## Архитектура Frontend

```
src/
├── pages/       — страницы (один компонент = один роут)
├── components/  — переиспользуемые UI-компоненты (одна ответственность)
├── stores/      — Pinia stores (единственное место управления состоянием)
├── composables/ — API-вызовы и переиспользуемая логика
└── router/      — конфигурация Vue Router
```

- **Состояние** — только через Pinia stores, не через `ref`/`reactive` напрямую в компонентах.
- **API-вызовы** — только через composables, не из компонентов напрямую.
- Новые роуты добавляются **перед** fallback-маршрутом `/:pathMatch(.*)* → NotFoundPage`.

## Ключевые пакеты

| Назначение      | Пакет                                                                            |
|-----------------|----------------------------------------------------------------------------------|
| UUID v7         | `symfony/uid` — `Uuid::v7()` в конструкторе сущности                             |
| PDF-генерация   | `dompdf/dompdf` + `league/commonmark` (Markdown → HTML → PDF)                    |
| FTS             | нативный SQL через Doctrine DBAL, PostgreSQL `tsvector` / `websearch_to_tsquery` |
| Граф            | D3.js v7 (force-directed layout, SVG)                                            |
| Графики         | Chart.js v4 + vue-chartjs v5                                                     |
| Diff-просмотр   | `diff` (diffWords)                                                               |

## Структура тестов Backend

```
tests/
├── Unit/Application/Service/   — unit-тесты сервисов (мокированные репозитории)
├── Integration/Action/         — интеграционные тесты контроллеров (реальная БД)
└── bootstrap.php
```

Unit-тесты используют `MockObject` для репозиториев. Integration-тесты используют `createClient()` (Symfony KernelBrowser) — вызов `static::getContainer()` должен быть ПОСЛЕ `createClient()`.

## Active Technologies
- PHP 8.4 + Symfony 8.0, Doctrine ORM 3.6 + DBAL (backend)
- Vue 3.5, Pinia 3.0, Vue Router 5.0, TypeScript 5.9 (frontend)
- PostgreSQL 18.1
