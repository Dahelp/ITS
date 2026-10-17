# Подключение push Авито

## Текущее состояние

Код сервера и Flutter-клиента готов к подключению, но реальная доставка **выключена**: проекта Firebase и APNs-ключа пока нет. Регистрация телефона в базе не доказывает доставку через Google/Apple; её необходимо проверить на устройствах.

Схема: Avito webhook → приватная файловая очередь → `bin/send_avito_push.php` → FCM HTTP v1 → Android / APNs → iPhone. Webhook не обращается к Firebase и базе, сохраняя короткое время ответа. Ошибка сохранения задания приводит к 503, чтобы Авито повторил событие.

Уведомление содержит только «Новое сообщение покупателя. Откройте чат», chat_id и message_id. Тексты, имя покупателя, реквизиты и документы в Firebase не передаются. Получатели — все зарегистрированные телефоны сотрудников с текущим доступом к чату, а не только назначенный ответственный. Исходящие и события с неизвестным автором не уведомляют.

## 1. Создать свой проект Firebase

В [Firebase Console](https://console.firebase.google.com/) создать проект. Для этой интеграции нужны Cloud Messaging и настройки приложений; Firestore, Analytics и Firebase Authentication в коде не используются.

Зарегистрировать приложения:

| Платформа | Идентификатор | Файл из Firebase |
|---|---|---|
| Android | `ru.itscenter.its_avito` | `google-services.json` |
| iOS | `ru.itscenter.itsAvito` | `GoogleService-Info.plist` |

Скопировать клиентские файлы в `mobile/avito_chat/android/app/google-services.json` и `mobile/avito_chat/ios/Runner/GoogleService-Info.plist`. Они исключены из Git. Файлы нужны нативному SDK при запуске приложения системой в фоне. Android применяет Google Services plugin только при наличии файла; iOS копирует plist в пакет и настраивает Firebase в AppDelegate. Обычная сборка без Firebase остаётся рабочей; PUSH_ENABLED=true без нативного файла завершает сборку ошибкой.

Из `mobile/avito_chat/firebase.example.json` сделать игнорируемый `firebase.local.json`:

```json
{
  "PUSH_ENABLED": true,
  "FIREBASE_PROJECT_ID": "идентификатор своего проекта",
  "FIREBASE_SENDER_ID": "номер проекта",
  "FIREBASE_ANDROID_APP_ID": "mobilesdk_app_id из google-services.json",
  "FIREBASE_ANDROID_API_KEY": "current_key из google-services.json",
  "FIREBASE_IOS_APP_ID": "GOOGLE_APP_ID из plist",
  "FIREBASE_IOS_API_KEY": "API_KEY из plist"
}
```

Эти значения должны соответствовать нативным файлам и одному проекту. Это клиентские идентификаторы Firebase, **не серверный приватный ключ**. Service account JSON нельзя помещать в приложение, assets, public или Git.

Сборка из mobile/avito_chat:

```text
flutter pub get
flutter build apk --profile --dart-define-from-file=firebase.local.json
```

Для CI клиентские файлы и firebase.local.json нужно отдельно подать из защищённой конфигурации сборки. Сейчас CI проверяет компиляцию режима без настроенного Firebase; настоящий проект не имитируется.

## 2. Настроить серверный ключ

В Google Cloud IAM создать сервисную учётную запись для FCM с разрешением отправки сообщений в своём проекте (роль Firebase Cloud Messaging API Admin). Включить Firebase Cloud Messaging API HTTP v1. Создать её JSON-ключ и сохранить **на сервере** в:

```text
<корень сайта>/storage/avito-push/service-account.json
```

Каталог storage исключён из Git и rsync-деплоя, находится за пределами public. Права каталога 0700, ключа 0600. Не присылать приватный ключ текстом в чат и не добавлять в репозиторий. Сервер использует строго OAuth endpoint Google, RS256 и scope firebase.messaging; адреса из ключевого JSON не используются как HTTP URL.

`php8.2 bin/setup_avito_workspace.php` создаёт таблицу устройств и выключенный `storage/avito-push/config.json`. После установки настоящего ключа записать в config.json `{"enabled":true}` и проверить:

```text
php8.2 bin/setup_avito_push.php
php8.2 bin/send_avito_push.php
```

Первый проверяет структуру конфигурации, второй обрабатывает очередь. Сам по себе setup не вызывает FCM и не доказывает корректность ключа в Google.

## 3. Подключить Apple Push для iPhone

В своей команде Apple Developer включить Push Notifications для App ID и загрузить APNs Authentication Key (.p8, Key ID, Team ID) в Cloud Messaging настройки iOS-приложения Firebase. Ключ .p8 хранить отдельно от исходников. В Xcode выбрать свою команду и профиль, содержащий push entitlement.

В проекте подготовлены `Runner.entitlements`, remote-notification background mode и окружение APNs: Debug — development, Release — production. Для необычных Profile/dev-подписей нужно согласовать APS_ENVIRONMENT с provisioning profile. Сборка без подписи не устанавливается на обычный iPhone и не проверяет APNs.

```text
flutter build ios --debug --dart-define-from-file=firebase.local.json
```

## 4. Включить уведомления на телефоне

Установить настроенную сборку, войти как сотрудник с доступом Авито. Профиль → «Включить уведомления» → разрешить системный запрос. Автоматический запрос разрешения при первом запуске не выполняется. iOS может сначала показать ожидание APNs; повторить после регистрации устройства/возврата в приложение.

Токен FCM привязан к текущей мобильной сессии. При смене токена он обновляется. При выходе, истечении сессии, смене пароля, роли или отключении доступа отправка блокируется серверной проверкой перед каждым получателем. Один FCM-токен принадлежит только одной учётной записи/сессии. «Отключить на этом телефоне» снимает серверную регистрацию и выключает автоинициализацию FCM; она не включается повторно при обычном входе/возврате.

Нажатие на уведомление запускает приложение, проверяет сессию через me и открывает чат. В foreground показывается внутреннее уведомление; история продолжает обновляться. В фоне ничего не помечается прочитанным. После принудительной остановки приложения системой/пользователем доставка зависит от ограничений ОС; проверять это отдельно.

## 5. Проверить доставку и отдельно включить расписание

Для проверки использовать согласованный тестовый диалог: прислать сообщение **со стороны покупателя**, запустить worker вручную и проверить Android в фоне/после закрытия и подписанный iPhone. Проверить, что исходящие сообщения не вызывают push; выход, отключение доступа и смена пароля прекращают доставку. Не использовать реальных покупателей для незапрошенных тестовых ответов.

После успешного теста обработчик нужно запускать регулярно. **Расписание не устанавливается кодом или деплоем.** Автоматическое добавление постоянной cron-задачи было отклонено проверкой разрешений; включение должно быть отдельно согласовано после подключения Firebase.

Готовый пример для существующего crontab хостинга, после подтверждения установки (подставить реальный абсолютный путь и PHP 8.2):

```text
* * * * * /path/to/php8.2 /absolute/site/bin/send_avito_push.php >> /absolute/site/storage/avito-push/worker.log 2>&1
```

Не заменять весь crontab: добавить одну строку, сохранив остальные задания. При таком расписании штатная задержка до минуты. Worker защищён flock от параллельного запуска, хранит результат по каждому телефону и повторяет только неуспешные доставки с увеличением интервала. UNREGISTERED удаляет устаревшую регистрацию; ошибки конфигурации не удаляют все телефоны. Слишком старые уведомления (старше часа) не отправляются, завершённые отметки хранятся до суток. FCM/сеть не дают гарантии exactly-once: при потере подтверждения возможен повтор, Android tag и APNs collapse-id объединяют ожидающие уведомления одного чата.

## Контроль

`GET push-status` (с Bearer) возвращает enabled, registered и безопасные данные регистрации текущей сессии; сырого FCM-токена в ответе нет. `POST push-register` принимает push_token и platform=android|ios, `POST push-unregister` снимает только текущую сессию. Отправка этих методов без авторизации невозможна.

Тесты: `php tests/AvitoPushTest.php`, `php tests/AvitoWebhookTest.php`, `node tests/AvitoMobileHttpTest.js`, `flutter test`, `flutter analyze`. Тесты проверяют криптографическую подпись OAuth на временном ключе и транспорт на заглушке, **не отправляют реальные push**.

Официальные инструкции: [Flutter/Firebase](https://firebase.google.com/docs/flutter/setup), [FCM Flutter](https://firebase.google.com/docs/cloud-messaging/flutter/get-started), [HTTP v1 и сервисная учётная запись](https://firebase.google.com/docs/cloud-messaging/send/v1-api), [открытие уведомлений](https://firebase.google.com/docs/cloud-messaging/flutter/receive-messages).
