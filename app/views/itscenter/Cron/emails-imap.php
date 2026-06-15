<?php

use app\services\admin\AdminActivityLogger;
use app\services\mail\SafeMailbox;
use PhpImap\Exceptions\ConnectionException;

$cronId = (int)($_GET['id'] ?? 0);
$limit = (int)($_GET['limit'] ?? 50);
$limit = max(1, min($limit, 100));
$viewcrons = $cronId > 0 ? \R::findOne('cron', 'id = ?', [$cronId]) : null;
$params = is_file(CONF . '/params.php') ? require CONF . '/params.php' : [];
$cfg = static fn(string $key, string $default = '') => (string)($params[$key] ?? $default);

$mailbox = new SafeMailbox(
    '{' . $cfg('imap_host') . ':' . $cfg('imap_port', '993') . '/imap/ssl}INBOX',
    $cfg('imap_login'),
    $cfg('imap_password'),
    false,
    'UTF-8'
);

try {
    $mailIds = $mailbox->searchMailbox('ALL') ?: [];
} catch (ConnectionException $ex) {
    exit('IMAP connection failed: ' . $ex->getMessage());
} catch (Exception $ex) {
    exit('An error occurred: ' . $ex->getMessage());
}

$lastMessageId = (int)\R::getCell("SELECT COALESCE(MAX(message_id), 0) FROM mails_imap WHERE folder IN ('', 'INBOX')");
$mailIds = array_values(array_filter(array_map('intval', $mailIds), static fn($id) => $id > $lastMessageId));
sort($mailIds);
$mailIds = array_slice($mailIds, 0, $limit);
$processed = 0;
$skipped = 0;

foreach ($mailIds as $mailId) {
    $exists = (int)\R::getCell(
        "SELECT COUNT(*) FROM mails_imap WHERE message_id = ? AND folder IN ('', 'INBOX')",
        [$mailId]
    );
    if ($exists > 0) {
        continue;
    }

    try {
        $email = $mailbox->getMail($mailId, false);
    } catch (\Throwable $e) {
        $skipped++;
        if (function_exists('cron_cli_log')) {
            cron_cli_log('SKIP id=' . $cronId . ' mail_id=' . $mailId . ' error=' . $e->getMessage());
        }
        continue;
    }
    $content = !empty($email->textHtml) ? $email->textHtml : ($email->textPlain ?? '');
    $fromAddress = (string)($email->fromAddress ?? '');
    $user = $fromAddress !== '' ? \R::findOne('user', 'email = ?', [$fromAddress]) : null;
    $emailTimestamp = !empty($email->date) ? strtotime((string)$email->date) : false;
    $dateEmail = $emailTimestamp ? date('Y-m-d H:i:s', $emailTimestamp) : date('Y-m-d H:i:s');
    $folder = property_exists($email, 'mailboxFolder') && $email->mailboxFolder ? (string)$email->mailboxFolder : 'INBOX';
    $flagged = property_exists($email, 'isFlagged') && $email->isFlagged ? '1' : '0';
    $seen = property_exists($email, 'isSeen') && $email->isSeen ? '1' : '0';
    try {
        $attachments = $email->getAttachments() ? '1' : '0';
    } catch (\Throwable $e) {
        $attachments = '0';
    }

    \R::exec(
        "INSERT INTO mails_imap
            (message_id, folder, from_mail, from_name, user_id, subject, content, date_dispatch, date_last_modified, id_flagged, is_seen, attachments)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $mailId,
            $folder,
            $fromAddress,
            h((string)($email->fromName ?? '')),
            $user ? (int)$user->id : null,
            h((string)($email->subject ?? '')),
            base64_encode((string)$content),
            $dateEmail,
            $dateEmail,
            $flagged,
            $seen,
            $attachments,
        ]
    );
    $processed++;
}

$mailbox->safeDisconnect();

if ($cronId > 0) {
    \R::exec("UPDATE cron SET date_update = ? WHERE id = ?", [date('Y-m-d H:i'), $cronId]);
    AdminActivityLogger::cron($cronId, PHP_SAPI !== 'cli', $_SESSION['user']['id'] ?? null);
}

$message = 'Почта обновлена. Новых писем: ' . $processed . '. Пропущено: ' . $skipped;
if (PHP_SAPI === 'cli') {
    echo $message . PHP_EOL;
    return;
}

$_SESSION['success'] = $viewcrons ? 'Задание "' . $viewcrons->name . '" выполнено! ' . $message : $message;
redirect(PATH . '/admin/cron');
