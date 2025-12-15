# Space Dashboard - Космический дашборд

Веб-приложение для сбора, визуализации и анализа данных о космических объектах и событиях.

## 📋 Содержание

- [Описание проекта](#описание-проекта)
- [Архитектура](#архитектура)
- [Структура проекта](#структура-проекта)
- [Страницы приложения](#страницы-приложения)
- [API эндпоинты](#api-эндпоинты)
- [Технологии](#технологии)
- [Запуск проекта](#запуск-проекта)
- [Особенности реализации](#особенности-реализации)

## 🚀 Описание проекта

Проект представляет собой распределенный монолит для работы с космическими данными:
- Сбор данных из внешних API (ISS, JWST, NASA OSDR, AstronomyAPI)
- Визуализация данных в виде дашбордов, графиков и галерей
- Генерация и экспорт CSV/XLSX файлов с телеметрией
- Кеширование и rate limiting для оптимизации производительности

## 🏗️ Архитектура

```
┌─────────────┐
│   Browser   │
└──────┬──────┘
       │
┌──────▼──────┐
│   Nginx     │ (Reverse Proxy)
└──────┬──────┘
       │
┌──────▼──────────────────────────────────┐
│         PHP/Laravel (php_web)            │
│  - Dashboard, ISS, JWST, Astro, CSV      │
│  - API Proxy endpoints                   │
│  - Rate Limiting (Redis)                 │
└──────┬───────────────────────────────────┘
       │
┌──────▼──────┐    ┌──────────────┐    ┌─────────────┐
│  Rust ISS   │    │   Redis      │    │ PostgreSQL  │
│  Service    │    │   (Cache)    │    │   (iss_db)  │
└──────┬──────┘    └──────────────┘    └─────────────┘
       │
┌──────▼──────┐
│ Pascal      │
│ Legacy      │ (CSV Generator)
└─────────────┘
```

### Сервисы

- **nginx** - Reverse proxy, маршрутизация запросов к PHP-FPM
- **php_web** - Laravel приложение с веб-интерфейсом и API
- **rust_iss** - Rust сервис для сбора данных из внешних API
- **iss_db** - PostgreSQL база данных
- **redis_cache** - Redis для кеширования и rate limiting
- **pascal_legacy** - Legacy модуль на Pascal для генерации CSV

## 📁 Структура проекта

```
he-path-of-the-samurai/
├── db/
│   └── init.sql                    # Схема базы данных
├── services/
│   ├── php-web/
│   │   ├── Dockerfile              # PHP контейнер
│   │   ├── entrypoint.sh           # Скрипт инициализации
│   │   ├── nginx.conf              # Конфигурация Nginx
│   │   └── laravel-patches/
│   │       ├── app/
│   │       │   ├── Http/
│   │       │   │   ├── Controllers/    # Контроллеры
│   │       │   │   ├── Middleware/     # RateLimitMiddleware
│   │       │   │   └── Validation/     # Классы валидации
│   │       │   └── Support/            # Вспомогательные классы
│   │       ├── resources/
│   │       │   └── views/              # Blade шаблоны
│   │       └── routes/
│   │           └── web.php             # Маршруты
│   ├── rust-iss/                      # Rust сервис
│   └── pascal-legacy/                 # Legacy модуль
├── docker-compose.yml                 # Конфигурация Docker
└── README.md                          # Этот файл
```

# Инструкция по запуску проекта

## Предварительные требования

1. Docker Desktop установлен и запущен
2. Docker Compose установлен
3. Порты 8080, 8081, 5432, 6379 свободны

## Запуск проекта

### Вариант 1: Через скрипт (Windows)

Bash


# Двойной клик на start.bat или выполнить в терминале:
start.bat

### Вариант 2: Вручную

Bash


# Перейти в директорию проекта
cd c:\Users\vanya\he-path-of-the-samurai

# Остановить существующие контейнеры (если есть)
docker-compose down

# Запустить проект
docker-compose up -d --build

# Просмотр логов
docker-compose logs -f

# Проверка статуса
docker-compose ps

## Доступ к приложению

- Веб-интерфейс: http://localhost:8080
- Rust API: http://localhost:8081
- PostgreSQL: localhost:5432
- Redis: localhost:6379

## Структура страниц

- /dashboard - Главная страница с обзором
- /iss - Данные МКС
- /osdr - NASA OSDR данные
- /jwst - Галерея JWST
- /astro - Астрономические события
- /csv - Визуализация CSV файлов

## API эндпоинты

- GET /api/iss/last - Последние данные МКС
- GET /api/iss/trend - Тренд движения МКС
- GET /api/jwst/feed - JWST изображения
- GET /api/astro/events - Астрономические события

Все API эндпоинты защищены Rate-Limit через Redis.

## Просмотр логов конкретного сервиса

Bash


docker-compose logs -f php
docker-compose logs -f rust_iss
docker-compose logs -f pascal_legacy
