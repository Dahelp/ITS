# ИТС Авито на iPhone

## Состояние

Интерфейс, документы, фотографии, push-обработчики, иконки и доступ сотрудников общие с Android. Bundle ID: `ru.itscenter.itsAvito`. Подписка Apple Developer пока не оформлена. Firebase iOS-приложение и APNs-ключ пока не предоставлены. Неподписанная сборка проверяет компиляцию и не устанавливается через TestFlight.

## Порядок подключения

1. Владелец оформляет Apple Developer Program со своими действительными данными и принимает соглашения самостоятельно. Стандартная стоимость $99/год; фактические способы оплаты показываются при оформлении. Баланс Apple Account и подарочные карты не подходят для оплаты этой подписки. Не приобретать их ради регистрации.
2. В Firebase-проекте `its-avito`: Project settings → General → Add app → iOS. Apple bundle ID — `ru.itscenter.itsAvito`, nickname — `ИТС Авито`; App Store ID можно пока пропустить. Скачать `GoogleService-Info.plist`.
3. Импортировать клиентскую конфигурацию из корня mobile/avito_chat: `python3 tool/configure_ios_firebase.py /path/to/GoogleService-Info.plist`. Скрипт проверяет проект и Bundle ID, сохраняет существующие Android-настройки и не печатает ключи. Клиентский plist не является серверным service-account JSON; они не взаимозаменяемы.
4. После активации Apple Developer зарегистрировать Explicit App ID `ru.itscenter.itsAvito`, включить Push Notifications. Создать APNs authentication key, сохранить .p8 отдельно от исходников. В Firebase → Project settings → Cloud Messaging → Apple app configuration загрузить APNs-ключ, Key ID и Team ID. Параметры среды и разрешённый App ID должны покрывать используемую сборку.
5. Для TestFlight создать запись приложения в App Store Connect с этим Bundle ID. Подготовить Apple Distribution certificate и App Store Connect provisioning profile с push entitlement. При сборке на Mac выбрать свою Team в `ios/Runner.xcworkspace`. Пароли Apple, .p8 и закрытые ключи не помещать в чат или Git.
6. Использовать Xcode 26+ и iOS SDK 26+, как требует Apple для загрузок с 28 апреля 2026. CI проверяет release-сборку без подписи на Xcode 26.3; это не подтверждает прохождение App Store Connect и доставку APNs.
7. На настроенном Mac: `flutter pub get`, затем `flutter build ipa --release --dart-define-from-file=firebase.local.json`. Архив подписывается средствами Xcode и своей Team. Загрузка в App Store Connect и приглашение тестировщиков выполняются после проверки подписанного результата.
8. Установить через TestFlight. Проверить вход, список/фильтры, документы, фото, воспроизведение голосовых. Профиль → включить уведомления: дождаться APNs и регистрации телефона. Проверить реальное входящее сообщение в фоне, открытие нужного товара из push, отсутствие доставки после выхода/отключения доступа.

## Проверка в GitHub Actions

Workflow `Avito mobile checks`, ручной запуск `ios=true`: компилирует iPhone-приложение без подписи и без Firebase. Для компиляции с настоящей iOS-конфигурацией добавить repository secret `FIREBASE_IOS_PLIST_BASE64` (base64 клиентского plist), выбрать `ios_firebase=true`. Скрипт повторно проверит проект и Bundle ID. Этот секрет не заменяет APNs-ключ, сертификат подписи и provisioning profile.

## Официальные инструкции

- https://developer.apple.com/help/account/membership/program-enrollment/
- https://developer.apple.com/help/account/membership/enrolling-in-the-app/
- https://developer.apple.com/news/upcoming-requirements/?id=02032026a
- https://firebase.google.com/docs/cloud-messaging/flutter/get-started
