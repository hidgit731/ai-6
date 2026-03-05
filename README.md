# Заметки — Приложение для ведения заметок

Полнофункциональное веб-приложение для ведения заметок с редактором Markdown, иерархическими папками, тегами,
полнотекстовым поиском, вики-ссылками и графом знаний, историей версий, экспортом (MD/PDF) и
дашбордом активности.

## Возможности

- **Редактор Markdown** с живым предпросмотром
- **Иерархические папки** для организации заметок
- **Теги** с облаком тегов и фильтрацией по нескольким тегам
- **Полнотекстовый поиск** (PostgreSQL tsvector + GIN-индекс)
- **Вики-ссылки** (`[[Название заметки]]`) с автосвязыванием между заметками
- **Граф знаний** (граф на основе D3.js с силовым расположением)
- **История версий** с просмотром различий и откатом в один клик
- **Экспорт** — скачивание заметки в формате `.md` или `.pdf`
- **Дашборд** — статистика активных заметок/тегов/папок + график активности за 30 дней
- **Избранное и Корзина** с мягким удалением

## Требования

- [Docker](https://www.docker.com/) 24+
- Docker Compose v2 (входит в состав Docker Desktop)

## Быстрый старт

#### 1. Подготовка env-файлов проекта в целом и его сервисов
```bash
cp .env.example .env && \
cp ./backend_monolith/.env.example ./backend_monolith/.env && \
cp ./frontend/.env.example ./frontend/.env
```

#### 2. Запустите сервисы
```bash
docker compose up -d
```

#### 3. Примените миграции базы данных
```bash
docker compose exec backend_monolith php bin/console doctrine:migrations:migrate --no-interaction
```

#### 4. (Опционально) Заполните базу демонстрационными данными
```bash
docker compose exec backend_monolith php bin/console doctrine:fixtures:load --no-interaction
```

#### 5. Откройте в браузере http://localhost:8080

> Порт по умолчанию — `8080`. При необходимости измените `APP_PORT` в `.env`.

## Технический стек

| Слой              | Технология                                       |
|-------------------|--------------------------------------------------|
| Backend           | PHP 8.4, Symfony 8.0                             |
| База данных       | PostgreSQL 18.1                                  |
| ORM               | Doctrine ORM 3.6 + DBAL                          |
| Генерация PDF     | Dompdf 3.x + league/commonmark 2.x               |
| Frontend          | Vue 3.5 + TypeScript 5.9                         |
| Состояние         | Pinia 3.0                                        |
| Маршрутизация     | Vue Router 5.0                                   |
| Графики           | Chart.js 4.x + vue-chartjs 5.x                   |
| Граф              | D3.js 7.x                                        |
| Сборка            | Vite 7.x                                         |
| Инфраструктура    | Docker Compose, nginx 1.27                       |

## Структура проекта

```
backend_monolith/   # Symfony REST API
  src/
    Domain/         # Сущности и интерфейсы репозиториев
    Application/    # Сервисы и DTO
    Infrastructure/ # Реализации на Doctrine
    Presentation/   # HTTP action-контроллеры
  tests/            # PHPUnit unit и интеграционные тесты
  migrations/       # Миграции Doctrine

frontend/           # Vue 3 SPA
  src/
    pages/          # Компоненты страниц Vue Router
    components/     # Переиспользуемые UI-компоненты
    stores/         # Pinia-хранилища состояния
    composables/    # API-вызовы и общая логика
    router/         # Конфигурация маршрутов

deploy/             # Конфигурация Docker и nginx
  dev/              # Настройки для разработки
  prod/             # Настройки для продакшна
```

## Запуск тестов

```bash
# Backend (PHPUnit) — первый запуск: создать тестовую БД и применить миграции
docker compose exec backend_monolith php bin/console --env=test doctrine:database:create
docker compose exec backend_monolith php bin/console --env=test doctrine:migrations:migrate --no-interaction

# Запуск тестов
docker compose exec backend_monolith php vendor/bin/phpunit

# Frontend (Vitest)
docker compose exec frontend npm test
```

## Проверка стиля кода

```bash
# PHP CS Fixer
docker compose exec backend_monolith php vendor/bin/php-cs-fixer fix

# Проверка типов TypeScript
docker compose exec frontend npm run type-check
```
