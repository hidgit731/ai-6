# Architecture — Проект «Заметки»

## Обзор

«Заметки» — веб-приложение для создания и управления заметками. Проект организован как **монорепозиторий** с двумя независимыми сервисами: REST API на Symfony и SPA на Vue 3.

## Технический стек

| Компонент        | Технология                          |
|------------------|-------------------------------------|
| Backend monolith | PHP 8.4 + Symfony 8                 |
| Frontend         | Vue 3 + Vue Router + Pinia          |
| База данных      | PostgreSQL 18+                      |
| Инфраструктура   | Docker Compose                      |
| CI               | GitHub Actions                      |
| Стиль кода PHP   | PHP CS-Fixer (`@Symfony`, `@Symfony:risky`) |
| Тесты PHP        | PHPUnit 11+                         |

## Структура репозитория

```
project-root/
├── backend_monolith/   # Symfony REST API
│   ├── src/
│   ├── tests/
│   ├── config/
│   ├── public/
│   ├── composer.json
│   └── deploy/         # Dockerfile и конфигурация для dev/prod
├── frontend/           # Vue 3 SPA
│   ├── src/
│   └── deploy/
├── deploy/             # Общая инфраструктура (nginx, postgres)
│   ├── dev/
│   └── prod/
├── specs/              # Спецификации фич (.specify workflow)
├── docker-compose.yaml
└── docker-compose.prod.yaml
```

## Архитектура Backend (`backend_monolith/`)

Backend реализует слоистую архитектуру (Layered / Clean Architecture). Зависимости направлены строго внутрь: `Presentation → Application → Domain`. Слой `Infrastructure` реализует интерфейсы `Domain`.

### Структура `src/`

```
src/
├── Domain/                         # Бизнес-ядро (независимо от фреймворка)
│   ├── Entity/                     # Doctrine entities с бизнес-правилами
│   ├── Repository/                 # Интерфейсы репозиториев (без реализаций)
│   └── Trait/                      # Общие трейты (напр. TimestampsTrait)
├── Application/                    # Use-cases и сервисы
│   ├── Service/                    # Бизнес-логика (оркестрация use cases)
│   └── DTO/
│       ├── Request/                # Входящие DTO (валидация через Symfony Validator)
│       └── Response/               # Исходящие DTO (сериализация в JsonResponse)
├── Infrastructure/                 # Реализации, зависящие от фреймворка
│   └── Persistence/
│       └── Repository/             # Doctrine ORM реализации репозиториев
└── Presentation/
    └── HTTP/                       # Action-контроллеры (один класс = __invoke())
```

### Ключевые архитектурные решения

**Тонкие контроллеры (Action Controllers)**

Каждый HTTP-контроллер — отдельный класс с единственным методом `__invoke()`. Контроллеры не наследуются от `AbstractController`, используют атрибут `#[AsController]`. Порядок работы контроллера:
1. Принять HTTP-запрос.
2. Заполнить и валидировать Request DTO через Symfony Validator.
3. Вызвать ровно один Application Service.
4. Вернуть `JsonResponse` из Response DTO.

Бизнес-логика в контроллерах **запрещена**. `Request`/`Response` объекты не выходят за пределы слоя `Presentation`.

**Сервисы как единственное место бизнес-логики**

`Application/Service/` — единственное место для бизнес-логики. Сервисы:
- зависят только от интерфейсов репозиториев (`Domain/Repository/`), не от Doctrine-классов;
- не имеют знания о HTTP (`Request`, `Response`);
- принимают зависимости через constructor injection;
- покрываются unit-тестами с замоканными репозиториями.

**Repository Pattern**

Для каждой сущности обязательно:
- **Интерфейс** в `Domain/Repository/` — описывает контракт без реализации.
- **Реализация** в `Infrastructure/Persistence/Repository/` — конкретный Doctrine ORM репозиторий.

Прямое использование `EntityManager` и конкретных Doctrine-репозиториев за пределами `Infrastructure` **запрещено**.

**DTO-ориентированный обмен данными**

- **Входящие данные**: `HTTP Request → Request DTO` (валидация через Symfony Validator до попадания в сервис).
- **Исходящие данные**: `результат сервиса → Response DTO` (сериализуется Symfony Serializer в `JsonResponse`).

Прямое чтение сырых данных из `Request` внутри сервисов **запрещено**.

## Архитектура Frontend (`frontend/`)

Frontend — Single Page Application на Vue 3. Взаимодействует с backend исключительно через HTTP API.

### Структура `src/`

```
src/
├── components/     # Переиспользуемые UI-компоненты (1 компонент = 1 ответственность)
├── pages/          # Страницы (привязаны к роутам Vue Router)
├── stores/         # Pinia stores (единственное место управления состоянием)
├── composables/    # API-вызовы и переиспользуемая логика
└── router/         # Конфигурация Vue Router
```

### Ключевые правила

- Состояние приложения управляется **только через Pinia stores**, не в компонентах напрямую.
- API-вызовы осуществляются **только через composables**, не из компонентов напрямую.
- Каждый компонент имеет одну зону ответственности.
- Интерфейс поддерживает **mobile-first, responsive-дизайн**.

## Взаимодействие сервисов

```
Browser
  │
  ▼
[frontend/]  ──HTTP/JSON──▶  [backend_monolith/]  ──SQL──▶  [PostgreSQL]
 Vue 3 SPA                    Symfony REST API
```

Сервисы взаимодействуют **исключительно через HTTP API-контракты**. Прямые импорты через границу сервисов **запрещены**.

## CI/CD

GitHub Actions запускает при каждом Pull Request:

| Шаг              | Команда                              |
|------------------|--------------------------------------|
| Стиль кода PHP   | `php-cs-fixer fix --dry-run`         |
| Тесты PHP        | `php bin/phpunit`                    |

## Принципы разработки

1. **SOLID** — каждый класс имеет одну зону ответственности, зависимости направлены через интерфейсы.
2. **KISS** — решения должны быть простыми и понятными без избыточной абстракции.
3. **Testability** — бизнес-логика изолирована и покрывается unit-тестами без запуска HTTP-стека или БД.
4. **Explicit contracts** — все данные на границах слоёв передаются через DTO с явными типами.
