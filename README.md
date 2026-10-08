# Серверная часть системы стриминга контента

Курсовая работа по дисциплине «Разработка серверных частей интернет-ресурсов».
PHP 8.3 · Slim 4 · PostgreSQL 16 · ffmpeg (HLS) · Clean Architecture.

## Запуск

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php bin/create-admin.php admin@example.com strongpass1
```

API: http://localhost:8080. Демо-страница: http://localhost:8080/demo.html (вход, каталог, плеер HLS, подписка). Воркер транскодирования стартует сервисом `worker`
(после `composer install` перезапустите его: `docker compose restart worker`).

Тесты: `docker compose exec app vendor/bin/phpunit` (15 тестов: 12 unit-тестов сценариев и 3 архитектурных теста правила зависимостей, `tests/Unit/ArchitectureTest.php`)

Секрет `JWT_SECRET` должен быть не короче 32 байт (требование firebase/php-jwt 7.x).

## Структура и правило зависимостей

```
src/
├── Domain/          1. Сущности: бизнес-правила, интерфейсы репозиториев   (зависит только от PHP)
├── Application/     2. Сценарии (UseCase) и порты (интерфейсы сервисов)     (зависит от Domain)
├── Interfaces/      3. Адаптеры: HTTP-контроллеры, PDO-репозитории          (зависит от Domain, Application)
└── Infrastructure/  4. БД-подключение, JWT, ffmpeg, файловое хранилище,
                        HTTP-middleware; Slim и PHP-DI подключаются здесь    (зависит от всех внутренних)
config/container.php — composition root: единственное место, где порты связываются с реализациями
```

Бизнес-правила в Domain: доступ к платному контенту только при активной подписке
(`Content::isAccessibleWith`), подписка активна на полуинтервале `[start, end)`
(`Subscription::isActiveAt`), позиция просмотра неотрицательна.

## API

| Метод | Путь | Доступ | Назначение |
|---|---|---|---|
| POST | `/auth/register` | все | регистрация `{email, password}` |
| POST | `/auth/login` | все | вход, возвращает JWT |
| GET | `/content?q=&genre=&page=&per_page=` | все | каталог опубликованного контента |
| POST | `/content` | admin | создать карточку контента |
| POST | `/content/{id}/media` | admin | загрузить видео (multipart, поле `file`), создаёт задачу транскодирования |
| POST | `/content/{id}/stream` | пользователь | проверка прав, возвращает подписанный URL манифеста |
| GET | `/stream/{id}/{file}?exp=&sig=` | по подписи | HLS-манифест и сегменты |
| POST | `/subscriptions` | пользователь | оформить подписку `{plan_id}` |
| PUT | `/content/{id}/progress` | пользователь | сохранить позицию `{position_seconds}` |
| GET | `/me/history` | пользователь | история просмотров |

### Сценарий

```bash
TOKEN=$(curl -s localhost:8080/auth/login -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"strongpass1"}' | jq -r .token)

curl -s localhost:8080/content -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"title":"Демо","genre":"documentary","release_year":2024,"is_free":true}'

curl -s localhost:8080/content/1/media -H "Authorization: Bearer $TOKEN" -F file=@video.mp4
# ждём, пока воркер переведёт контент в статус published, затем:
curl -s -X POST localhost:8080/content/1/stream -H "Authorization: Bearer $TOKEN"
# => {"url":"/stream/1/master.m3u8?exp=...&sig=..."} — его можно открыть в любом HLS-плеере
```

## Как устроена выдача видео

1. Клиент запрашивает `POST /content/{id}/stream` с JWT. Сценарий `RequestStream` проверяет, что контент
   опубликован и что у пользователя есть право (бесплатный или активная подписка).
2. Возвращается URL с параметрами `exp` и `sig` (HMAC-SHA256 от `content:{id}|{exp}`), действует `STREAM_URL_TTL` секунд.
3. Плеер запрашивает манифест и сегменты без JWT — доступ проверяется по подписи. В манифест на лету
   дописывается подпись к ссылкам на сегменты.
4. Имена файлов проходят белый список (`[A-Za-z0-9_-]+\.(m3u8|ts)`), выход за каталог исключён.

## Ограничения текущей версии

- Одно качество (разрешение исходника) и один плейлист; адаптивный битрейт (несколько вариантов +
  настоящий master-плейлист) — направление развития. Файл назван `master.m3u8` ради единообразия API.
- Файлы раздаёт PHP. В продакшене их отдают через nginx/CDN, проверяя подпись (`secure_link`).
- Очередь задач реализована в PostgreSQL (`FOR UPDATE SKIP LOCKED`); при росте нагрузки её заменяют
  на брокер (RabbitMQ, Redis).
- Платёжный шлюз не подключён: подписка оформляется без оплаты.

## Документация

Пояснительная записка курсовой работы: [`docs/KR_RSCHIR_Polny_otchet.docx`](docs/KR_RSCHIR_Polny_otchet.docx) (PDF-версия — рядом).

> Значения в `.env.example` — только для локального запуска. Для любого другого окружения замените `JWT_SECRET` и `URL_SIGN_SECRET`.
