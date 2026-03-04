# Architecture — Проект «Заметки»

## Обзор

«Заметки» — веб-приложение для создания и управления заметками. Проект организован как **монорепозиторий** с двумя независимыми сервисами: REST API на Symfony и SPA на Vue 3.

## Технический стек

| Компонент           | Технология                                                           |
|---------------------|----------------------------------------------------------------------|
| Backend monolith    | PHP 8.4 + Symfony 8                                                  |
| ORM                 | Doctrine ORM 3.6 + Doctrine Migrations                               |
| UUID                | symfony/uid (UUID v7)                                                |
| API-документация    | nelmio/api-doc-bundle 5.9 (Swagger UI)                               |
| Frontend            | Vue 3.5 + Vue Router 5.0 + Pinia 3.0                                 |
| Markdown            | marked 17.x + DOMPurify 3.3.x                                        |
| Сборка Frontend     | Vite 7.3 + TypeScript 5.9                                            |
| Стили Frontend      | Sass 1.97 (SCSS)                                                     |
| Тесты Frontend      | Vitest 4.0 (jsdom environment)                                       |
| База данных         | PostgreSQL 18+                                                       |
| Инфраструктура      | Docker Compose                                                       |
| CI                  | GitHub Actions                                                       |
| Стиль кода PHP      | PHP CS-Fixer (`@Symfony`, `@Symfony:risky`)                          |
| Тесты PHP           | PHPUnit 13+                                                          |

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
├── assets/
│   └── styles/         # Глобальные SCSS-стили
│       ├── _breakpoints.scss   # Брейкпоинты для responsive-дизайна
│       ├── _reset.scss         # CSS-reset
│       ├── _variables.scss     # CSS/SCSS переменные
│       └── main.scss           # Точка входа стилей
├── components/     # Переиспользуемые UI-компоненты (1 компонент = 1 ответственность)
├── composables/    # API-вызовы и переиспользуемая логика
├── pages/          # Страницы (привязаны к роутам Vue Router)
├── router/         # Конфигурация Vue Router
└── stores/         # Pinia stores (единственное место управления состоянием)
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

---

## Changelog

### [006] Tags System — 2026-03-05

Реализована система тегирования заметок: поддержка создания и удаления тегов, привязка множественных тегов к заметкам, фильтрация заметок по тегам, облако тегов с подсчётом количества заметок.

**Backend — добавлено:**

- **`src/Domain/Entity/Tag`** — сущность с полями `id` (UUID v7), `name` (VARCHAR 50, UNIQUE), `created_at`, `updated_at`. Индекс по `name`.
- **`src/Domain/Entity/Note`** — добавлено поле `tags` (ManyToMany → Tag с JoinTable `note_tag`, inversedBy='notes', ON DELETE CASCADE для обеих FK).
- **`src/Domain/Repository/TagRepositoryInterface`** — контракт: `save`, `findById`, `findByName`, `findPaginated`, `findCloud`, `delete`.
- **`src/Infrastructure/Persistence/Repository/DoctrineTagRepository`** — реализация на Doctrine ORM:
  - `findByName(array $names)` — поиск по массиву имён тегов.
  - `findCloud()` — DQL запрос с `COUNT(n.id)` для облака тегов; возвращает `[[$tag, 'noteCount' => int]]`.
- **`src/Application/DTO/Request/`** — `CreateTagRequest`, `SuggestTagsRequest`; расширены `CreateNoteRequest`, `UpdateNoteRequest`, `ListNotesRequest` (поле `tag_names[]`).
- **`src/Application/DTO/Response/`** — `TagResponse`, `TagCloudItemResponse`; расширены `NoteResponse`, `NoteListItemResponse` (поле `tags[]`).
- **`src/Application/Service/TagService`** — оркестрирует операции с тегами:
  - `create` — создание тега с проверкой уникальности имени.
  - `delete(id)` — удаление тега с каскадным удалением из `note_tag`.
  - `getCloud()` — возвращает облако тегов (`TagCloudItemResponse[]`).
  - `suggest(prefix)` — поиск тегов по префиксу имени (для автодополнения).
- **`src/Application/Service/NoteService`** — расширен:
  - принимает `TagRepositoryInterface` третьим аргументом конструктора.
  - метод `syncTags()` — синхронизация тегов при `create()`/`update()` (удаление, добавление новых).
  - фильтрация `list()` по `tag_names[]` — AND-логика через `INNER JOIN` для каждого тега.
  - EC-7: предварительная фильтрация неизвестных имён тегов перед запросом в репозиторий.
- **Новые Action-контроллеры** (`src/Presentation/HTTP/`):

  | Контроллер           | Маршрут                      |
  |----------------------|------------------------------|
  | `CreateTagAction`    | `POST /api/tags`             |
  | `DeleteTagAction`    | `DELETE /api/tags/{id}`      |
  | `ListTagsAction`     | `GET /api/tags`              |
  | `SuggestTagsAction`  | `GET /api/tags/suggest?q=`   |

- **`migrations/Version20260305000001`** — создание таблицы `tag` (UUID PK, VARCHAR 50 UNIQUE name, created_at, updated_at); создание JoinTable `note_tag` (ManyToMany связь Note↔Tag, ON DELETE CASCADE обе FK); добавление поля `tags` в Note.

**Backend — тесты:**

| Файл                                                | Тип         | Описание                                                                               |
|-----------------------------------------------------|-------------|----------------------------------------------------------------------------------------|
| `tests/Unit/Application/Service/TagServiceTest`     | Unit        | Создание, удаление, облако, поиск по префиксу — с моками репозитория                   |
| `tests/Unit/Application/Service/NoteServiceTagTest` | Unit        | Синхронизация тегов при create/update, EC-7 фильтрация неизвестных — мокированные теги |
| `tests/Integration/Action/TagActionsTest`           | Integration | POST /api/tags, DELETE /api/tags/{id}, GET /api/tags, GET /api/tags/suggest            |
| `tests/Integration/Action/NoteTagFilterTest`        | Integration | GET /api/notes?tag_names[]=... (AND-фильтр), структура ответа с тегами                 |

**Frontend — добавлено:**

- **`src/stores/tags.ts`** (Pinia) — состояние облака тегов, выбранных тегов для фильтрации; actions: `fetchCloud`, `fetchSuggestions`, `createTag`, `deleteTag`, `selectTag`, `deselectTag`.
- **`src/composables/useTags.ts`** — все HTTP-вызовы к `/api/tags` (GET cloud, POST, DELETE, GET suggest).
- **Новые компоненты**:
  - `TagCloud.vue` — облако тегов с кликабельными элементами, отображает количество заметок для каждого тега.
  - `TagInput.vue` — поле ввода тегов с автодополнением (composable `useTagSuggestions`), теги отображаются как чипсы, есть возможность удалить через `×`.
  - `NoteCard.vue` — расширена: отображает теги заметки как чипсы.
- **`src/pages/NotesListPage.vue`** — расширена: отображает облако тегов, фильтрация заметок по выбранным тегам (AND-логика).
- **`src/pages/NoteEditPage.vue`** — расширена: компонент `TagInput` для управления тегами заметки при create/edit.
- **`src/stores/notes.ts`** — синхронизирован с выбранными тегами из `useTagsStore`.
- **Новые пакеты**: нет новых пакетов (используются существующие).

**Frontend — тесты:**

| Файл                                            | Тип            | Описание                                       |
|-------------------------------------------------|----------------|------------------------------------------------|
| `src/components/__tests__/TagCloud.test.ts`     | Unit (Vitest)  | Рендеринг облака, клик на тег, работа с пустым |
| `src/components/__tests__/TagInput.test.ts`     | Unit (Vitest)  | Добавление/удаление тегов, автодополнение      |

**API — эндпоинты:**

| Метод     | Путь                              | Действие                                |
|-----------|-----------------------------------|-----------------------------------------|
| `GET`     | `/api/tags`                       | Облако тегов (счётчик заметок)          |
| `POST`    | `/api/tags`                       | Создание нового тега                    |
| `DELETE`  | `/api/tags/{id}`                  | Удаление тега (каскадное из note_tag)   |
| `GET`     | `/api/tags/suggest?q=<prefix>`    | Автодополнение (поиск по префиксу)      |
| `GET`     | `/api/notes?tag_names[]=...[]=..` | Фильтр заметок по тегам (AND-логика)    |

---

### [005] Notes Folders — 2026-03-04

Реализована иерархическая система папок для заметок: самоссылочное дерево папок (до 5 уровней вложенности), привязка заметок к папкам, перемещение папок и заметок между папками.

**Backend — добавлено:**

- **`src/Domain/Entity/Folder`** — самоссылочная сущность: `id` (UUID v7), `name` (VARCHAR 255), `parent` (nullable FK на себя, ON DELETE RESTRICT), `children` (OneToMany), `notes` (OneToMany), `created_at`, `updated_at`. Индекс по `parent_id`.
- **`src/Domain/Entity/Note`** — добавлено поле `folder` (nullable ManyToOne → Folder, ON DELETE SET NULL), индекс `idx_note_folder_id`.
- **`src/Domain/Repository/FolderRepositoryInterface`** — контракт: `save`, `findById`, `findAll`, `findByParentAndName`, `delete`.
- **`src/Infrastructure/Persistence/Repository/DoctrineFolderRepository`** — реализация на Doctrine ORM; `findByParentAndName()` обрабатывает `null`-родителя через `f.parent IS NULL`.
- **`src/Application/DTO/Request/`** — `CreateFolderRequest`, `UpdateFolderRequest`, `MoveFolderRequest`, `MoveNoteToFolderRequest`; расширены `CreateNoteRequest`, `UpdateNoteRequest`, `ListNotesRequest` (поле `folder_id`).
- **`src/Application/DTO/Response/`** — `FolderResponse`, `FolderTreeNodeResponse`; расширены `NoteResponse`, `NoteListItemResponse` (поле `folder_id`).
- **`src/Application/Service/FolderService`** — оркестрирует операции с папками:
  - `create` / `update` — создание и переименование с проверкой уникальности в пределах родителя.
  - `delete(id, strategy)` — удаление: `strategy=reassign` поднимает дочерние папки и заметки к родителю, `strategy=remove` рекурсивно удаляет. Отклоняет удаление папок с детьми без явной стратегии (`400`).
  - `getTree()` — возвращает дерево всех папок (`FolderTreeNodeResponse[]`).
  - `move()` — перемещает папку с проверкой глубины вложенности (`MAX_DEPTH = 5`).
- **`src/Application/Service/NoteService`** — расширен: принимает `FolderRepositoryInterface` вторым аргументом конструктора; поддерживает фильтрацию `list()` по `folder_id`, привязку папки при `create`/`update`, метод `moveToFolder()`.
- **Новые Action-контроллеры** (`src/Presentation/HTTP/`):

  | Контроллер               | Маршрут                      |
  |--------------------------|------------------------------|
  | `CreateFolderAction`     | `POST /api/folders`          |
  | `UpdateFolderAction`     | `PUT /api/folders/{id}`      |
  | `DeleteFolderAction`     | `DELETE /api/folders/{id}`   |
  | `GetFolderTreeAction`    | `GET /api/folders/tree`      |
  | `MoveFolderAction`       | `PUT /api/folders/{id}/move` |
  | `MoveNoteToFolderAction` | `PUT /api/notes/{id}/move`   |

- **`migrations/Version20260304000001`** — создание таблицы `folder`; `UNIQUE NULLS NOT DISTINCT (parent_id, name)` (PostgreSQL 15+, гарантирует уникальность имён внутри одного родителя, включая `NULL`-родителя); добавление `folder_id` в `note`; FK `ON DELETE RESTRICT` (папка) и `ON DELETE SET NULL` (заметка).

**Backend — тесты:**

| Файл                                               | Тип  | Описание                                                                                                     |
|----------------------------------------------------|------|--------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Application/Service/FolderServiceTest` | Unit | Создание, переименование, удаление (со стратегиями), перемещение, ограничение глубины — с моками репозитория |

**Frontend — добавлено:**

- **`src/stores/folders.ts`** (Pinia) — состояние дерева папок, выбранной папки, развёрнутых узлов (персистентность через `localStorage`); actions: `fetchTree`, `createFolder`, `updateFolder`, `deleteFolder`, `moveFolder`, `selectFolder`, `toggleExpanded`.
- **`src/composables/useFolders.ts`** — все HTTP-вызовы к `/api/folders` (GET tree, POST, PUT, DELETE, PUT move).
- **`src/composables/useToast.ts`** — система toast-уведомлений (создание, авто-удаление).
- **Новые компоненты**:
  - `FolderTree.vue` — дерево папок с пунктом «Все заметки» и рекурсивными узлами.
  - `FolderTreeNode.vue` — узел дерева с раскрытием/сворачиванием и выделением.
  - `FolderContextMenu.vue` — контекстное меню папки (переименовать, переместить, удалить); оптимистичное удаление: `204` → тихое, `400` → диалог стратегии.
  - `DeleteFolderDialog.vue` — диалог выбора стратегии удаления (`reassign` / `remove`).
  - `MoveFolderModal.vue` — модальное окно перемещения папки в другой родительский узел.
  - `MoveNoteModal.vue` — модальное окно перемещения заметки в папку.
  - `SkeletonList.vue` — скелетон-загрузка для списков.
  - `ToastContainer.vue` — контейнер для отображения toast-уведомлений.
- **`AppLayout.vue`** — интегрирован `FolderTree`; боковая панель с деревом папок.
- **`NoteCard.vue`** — расширен: отображает принадлежность заметки к папке.
- **`NotesListPage.vue`** — фильтрация заметок по выбранной папке.
- **`src/stores/notes.ts`** — синхронизирован с выбранной папкой из `useFoldersStore`.

**API — добавленные эндпоинты:**

| Метод    | Путь                                            | Действие                    |
|----------|-------------------------------------------------|-----------------------------|
| `GET`    | `/api/folders/tree`                             | Дерево папок                |
| `POST`   | `/api/folders`                                  | Создание папки              |
| `PUT`    | `/api/folders/{id}`                             | Переименование папки        |
| `DELETE` | `/api/folders/{id}[?strategy=reassign\|remove]` | Удаление папки              |
| `PUT`    | `/api/folders/{id}/move`                        | Перемещение папки           |
| `PUT`    | `/api/notes/{id}/move`                          | Перемещение заметки в папку |

---

### [004] Notes CRUD & Markdown Editor — 2026-03-04

Реализован полный CRUD заметок: REST API на Symfony и SPA-интерфейс на Vue 3 с Markdown-редактором.

**Backend — добавлено:**

- **`src/Domain/Entity/Note`** — сущность с полями `id` (UUID v7), `title` (VARCHAR 255), `content` (TEXT, nullable), `created_at`, `updated_at`. Индекс по `created_at` для сортировки.
- **`src/Domain/Trait/TimestampsTrait`** — трейт с `@ORM\PrePersist` / `@ORM\PreUpdate` для автоматического проставления временных меток.
- **`src/Domain/Repository/NoteRepositoryInterface`** — контракт: `findById`, `findPaginated`, `save`, `delete`.
- **`src/Infrastructure/Persistence/Repository/DoctrineNoteRepository`** — реализация на Doctrine ORM; для пагинации использует отдельный `QueryBuilder` для COUNT (избегает конфликта `ORDER BY` + `COUNT` в PostgreSQL).
- **`src/Application/DTO/Request/`** — `CreateNoteRequest`, `UpdateNoteRequest`, `ListNotesRequest` (валидация через Symfony Validator).
- **`src/Application/DTO/Response/`** — `NoteResponse`, `NoteListItemResponse`, `PaginatedNotesResponse`.
- **`src/Application/Service/NoteService`** — оркестрирует CRUD: `create`, `update`, `get`, `list` (пагинация, 10 записей/страница), `delete`.
- **`src/Presentation/HTTP/`** — пять Action-контроллеров (`CreateNoteAction`, `GetNoteAction`, `UpdateNoteAction`, `DeleteNoteAction`, `ListNotesAction`). Каждый — отдельный класс с `__invoke()`, атрибут `#[AsController]`.
- **`migrations/Version20260303182430`** — создание таблицы `note` + индекс `idx_note_created_at`.
- **`nelmio/api-doc-bundle 5.9`** — Swagger UI доступен по `/api/doc`.
- **`symfony/uid`** — генерация UUID v7 (монотонно возрастающие, дружественные к индексам БД).
- **Новые пакеты**: `doctrine/orm`, `doctrine/doctrine-bundle`, `doctrine/doctrine-migrations-bundle`, `symfony/uid`, `nelmio/api-doc-bundle`, `symfony/twig-bundle` (требуется NelmioApiDoc).
- **Dev-зависимости**: `symfony/browser-kit`, `symfony/http-client` — для интеграционных тестов.

**Backend — тесты:**

| Файл                                                         | Тип                 | Описание                                    |
|--------------------------------------------------------------|---------------------|---------------------------------------------|
| `tests/Unit/Application/Service/NoteServiceCreateTest`       | Unit                | Создание заметки, мок репозитория           |
| `tests/Unit/Application/Service/NoteServiceUpdateTest`       | Unit                | Обновление заметки, 404 при отсутствии      |
| `tests/Integration/Presentation/HTTP/ListNotesActionTest`    | Integration         | GET /api/notes, пагинация, структура ответа |
| `tests/Integration/Presentation/HTTP/ApiDocAvailabilityTest` | Integration (smoke) | Доступность Swagger UI                      |

**Frontend — добавлено:**

- **`src/stores/notes.ts`** (Pinia) — состояние списка заметок, текущей заметки, пагинации; actions: `fetchNotes`, `fetchNote`, `createNote`, `updateNote`, `deleteNote`.
- **`src/composables/useNotes.ts`** — все HTTP-вызовы к `/api/notes` (GET, POST, PUT, DELETE).
- **`src/composables/useMarkdown.ts`** — рендеринг Markdown через `marked` + санитизация `DOMPurify`.
- **Новые страницы** (роуты Vue Router):

  | Маршрут           | Страница        | Назначение                      |
  |-------------------|-----------------|---------------------------------|
  | `/notes`          | `NotesListPage` | Список заметок с пагинацией     |
  | `/notes/new`      | `NoteEditPage`  | Создание заметки                |
  | `/notes/:id`      | `NoteViewPage`  | Просмотр с Markdown-рендерингом |
  | `/notes/:id/edit` | `NoteEditPage`  | Редактирование заметки          |

- **Новые компоненты**:
  - `MarkdownEditor.vue` — textarea с живым предпросмотром Markdown (split-view).
  - `MarkdownPreview.vue` — рендеринг HTML из Markdown-строки (DOMPurify).
  - `NoteCard.vue` — карточка заметки в списке.
  - `Pagination.vue` — универсальный компонент постраничной навигации.
  - `ConfirmDialog.vue` — модальное окно подтверждения (используется при удалении).
- **Новые пакеты**: `marked ^17.0.3`, `dompurify ^3.3.1`, `@types/dompurify`, `jsdom ^28.1.0` (Vitest environment).
- **`vite.config.ts`** — добавлен `test.environment: 'jsdom'` для Vitest.

**Frontend — тесты:**

| Файл                                  | Тип           | Описание                            |
|---------------------------------------|---------------|-------------------------------------|
| `src/composables/useMarkdown.test.ts` | Unit (Vitest) | Рендеринг Markdown, санитизация XSS |

**Инфраструктура:**

- **`deploy/dev/nginx/nginx.conf`** — добавлен роут `/api/doc` для Swagger UI (проксирование в PHP-FPM).

**API — эндпоинты:**

| Метод    | Путь              | Действие                              |
|----------|-------------------|---------------------------------------|
| `GET`    | `/api/notes`      | Список заметок (пагинация: `?page=1`) |
| `POST`   | `/api/notes`      | Создание заметки                      |
| `GET`    | `/api/notes/{id}` | Получение заметки по UUID             |
| `PUT`    | `/api/notes/{id}` | Обновление заметки                    |
| `DELETE` | `/api/notes/{id}` | Удаление заметки                      |
| `GET`    | `/api/doc`        | Swagger UI (NelmioApiDoc)             |

---

### [003] Docker Infrastructure Initialized — 2026-03-04

Настроено полное Docker Compose окружение для dev и prod.

**Добавлено:**

- **`docker-compose.yaml`** (dev) — 4 сервиса:
  - `notes_nginx` (`nginx:1.27-alpine`) — точка входа, маршрутизация `/api/` → PHP-FPM, `/` → Vite dev-сервер
  - `notes_postgres` (`postgres:18.1-bookworm`) — БД с healthcheck и именованными volumes
  - `notes_backend_monolith` (`php:8.4-fpm-alpine`) — PHP-FPM с Xdebug (trigger mode), bind-mount исходников
  - `notes_frontend` (`node:20-alpine`) — Vite dev-сервер с bind-mount и изолированным `node_modules`
- **`docker-compose.prod.yaml`** — prod override: `restart: unless-stopped`, убран bind-mount исходников, закрыт порт БД
- **`backend_monolith/deploy/dev/Dockerfile`** — `php:8.4-fpm-alpine`, Composer 2.9.5, Xdebug 3.5.0, non-root пользователь через `PUID`/`PGID`
- **`backend_monolith/deploy/prod/Dockerfile`** — multi-stage: `builder` (composer install --no-dev) → `production`
- **`backend_monolith/deploy/dev/php/php.ini`** и **`backend_monolith/deploy/prod/php/php.ini`** — кастомные php.ini для dev/prod
- **`frontend/deploy/dev/Dockerfile`** — `node:20-alpine`, non-root пользователь, `CMD npm run dev --host 0.0.0.0`
- **`frontend/deploy/prod/Dockerfile`** — multi-stage: `node:20-alpine` (сборка `dist/`) → `nginx:1.27-alpine` (отдача статики + SPA fallback)
- **`deploy/dev/nginx/nginx.conf`** — FastCGI для `/api/`, WebSocket proxy для HMR Vite
- **`deploy/prod/nginx/nginx.conf`** — FastCGI для `/api/`, статика из `dist/` с SPA fallback
- **`.env.example`** (корень) — `APP_PORT`, `FORWARD_DB_PORT`, `DB_*`, `HOST_USER_UID/GID`, пути к кэшу Composer
- **`backend_monolith/.env.example`** — `APP_ENV`, `APP_SECRET`, `DATABASE_URL`
- **`.editorconfig`** — UTF-8, LF, 4 spaces; docker-compose файлы — 2 spaces

**Архитектурные решения:**

- Именованные Docker volumes (`postgres_data`, `postgres_dump`, `frontend_node_modules`) — данные не хранятся в bind-mount
- Сеть `notes_network` (bridge) — все сервисы объявляют её явно
- В dev исходный код монтируется через bind-mount, в prod — копируется в образ через multi-stage build
- Xdebug только в dev-образе, в режиме `start_with_request=trigger` (не замедляет каждый запрос)
- Контейнеры запускаются от non-root пользователя (кроме nginx), UID/GID пробрасываются через build args

---

### [002] Frontend Initialized — 2026-03-01

Инициализирован фронтенд-сервис (`frontend/`) на базе Vue 3 + Vite.

**Добавлено:**

- Scaffold Vue 3.5 SPA: `main.ts`, `App.vue`, `index.html`
- **Vue Router 5.0** — конфигурация в `src/router/index.ts`; маршруты: `/` (`HomePage`), `*` (`NotFoundPage`)
- **Pinia 3.0** — пример store в `src/stores/example.ts`
- **Composables** — пример API-вызовов в `src/composables/useExample.ts`
- **Компоненты** — `AppLayout.vue` (корневой layout с шапкой и подвалом)
- **SCSS-система стилей** (`src/assets/styles/`):
  - `_reset.scss` — CSS-reset
  - `_variables.scss` — дизайн-токены (цвета, типографика, отступы)
  - `_breakpoints.scss` — брейкпоинты для mobile-first адаптивности
  - `main.scss` — точка подключения всех стилей
- **TypeScript** — `tsconfig.json`, `tsconfig.app.json`, `tsconfig.node.json` (strict mode)
- **Vite 7.3** — `vite.config.ts` с плагином `@vitejs/plugin-vue`
- **Vitest 4.0** — подключён для unit-тестирования компонентов
- `frontend/.env.example` — переменные `VITE_API_BASE_URL`, `VITE_PORT`
- `frontend/.gitignore`

**Технические решения:**

- Сборщик Vite выбран вместо Webpack — быстрый HMR, нативный ESM в dev-режиме
- Sass (SCSS) вместо CSS Modules — удобнее для глобальных переменных и брейкпоинтов
- Vitest вместо Jest — нулевая настройка в связке с Vite, единый конфиг

---

### [001] Backend Initialized — до 2026-03-01

Инициализирован бэкенд-сервис (`backend_monolith/`) на базе Symfony 8.

**Добавлено:**

- Symfony 8 skeleton с PHP 8.4
- `symfony/validator`, `symfony/serializer` — валидация и сериализация DTO
- `friendsofphp/php-cs-fixer ^3.0` — линтер PHP (профили `@Symfony`, `@Symfony:risky`)
- `phpunit/phpunit ^13.0` — фреймворк тестирования
- Слоистая архитектура `src/`: `Domain`, `Application`, `Infrastructure`, `Presentation`
- Docker-окружение: `backend_monolith/deploy/dev/`, `backend_monolith/deploy/prod/`
