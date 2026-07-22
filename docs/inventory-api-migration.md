# Миграция остатков FTP → API 1С

## Аудит

- Активная CLI-точка `public/cron/run_task_cli.php` для `refresh-tovars-server` фактически вызывает `Cron::processFileBatch()` и обновляет `product`, `modification`, `in_stock`, `in_stock_history` из CSV.
- Файловые точки: `Cron::downloadCronFile()`, `processFileBatch()`, `public/cron/run_refresh_from_file*.php`, `refresh-tovars-server.php`; HTTP-controller закрыт кодом 410.
- Источник URL хранится в `cron.alias`; старые FTP credentials остаются в `config/params.php`, но новый sync их не использует.
- Витрина читает остатки из `product.quantity/rest/reserve`, `modification.quantity` и `in_stock`.

## Включение

1. Shadow: `php bin/sync_inventory_api.php --id=36 --categories=9,18,19 --mode=shadow`.
2. Тройная сверка: `php bin/compare_inventory_sources.php --file=public/cron/tovars.csv --limit=100`. Команда ничего не записывает в БД. FTP-снимок от 8 июля является историческим и не используется как источник истины; актуальным источником считается API 1С.
3. Сверить JSON-статистику и `storage/logs/inventory_api.jsonl`, повторить на полном наборе.
4. Canary 5%: `--mode=canary --canary-percent=5`; затем 25%, 50% и 100%.
5. Live: `--mode=live`. Только после успешного периода заменить production cron-команду. Старую файловую команду оставить выключенной как ручной rollback.

По умолчанию переход положительного остатка в ноль блокируется. После подтверждения shadow-сравнением штатные нулевые переходы разрешаются переменной `INVENTORY_ALLOW_ZERO_TRANSITIONS=1`. При timeout/HTTP/JSON ошибке используется кеш до `INVENTORY_API_STALE_TTL`, затем данные БД не меняются.

Настройки: `INVENTORY_API_CONNECT_TIMEOUT` (3), `INVENTORY_API_TIMEOUT` (8), `INVENTORY_API_CACHE_TTL` (60), `INVENTORY_API_STALE_TTL` (86400).

## Production deploy

Workflow `.github/workflows/deploy-beget.yml` доставляет код. Секреты API не менять в репозитории; на сервере задать config/env, выполнить shadow и canary вручную, затем изменить cron в панели Beget. Автоматический deploy/переключение не выполняется без production-доступа и подтверждённых результатов сравнения.
