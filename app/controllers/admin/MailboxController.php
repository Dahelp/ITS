<?php

namespace app\controllers\admin;

use app\models\admin\Mails;
use app\services\admin\AdminActivityLogger;
use app\services\mail\SafeMailbox;
use ishop\App;

class MailboxController extends AppController
{
    private array $folders = [
        'inbox' => ['title' => 'Входящие письма', 'aliases' => ['', 'INBOX']],
        'Sent' => ['title' => 'Отправленные письма', 'aliases' => ['Sent', 'INBOX.Sent']],
        'Drafts' => ['title' => 'Черновики', 'aliases' => ['Drafts', 'INBOX.Drafts']],
        'Junk' => ['title' => 'Спам', 'aliases' => ['Junk', 'Spam', 'INBOX.Junk', 'INBOX.Spam']],
        'Trash' => ['title' => 'Удалённые письма', 'aliases' => ['Trash', 'Deleted', 'INBOX.Trash', 'INBOX.Deleted']],
    ];

    public function indexAction()
    {
        $currentFolder = $this->currentFolder();
        $folderTitle = $this->folders[$currentFolder]['title'];
        $folderStats = $this->folderStats();

        $this->setMeta($folderTitle);
        $this->set(compact('currentFolder', 'folderTitle', 'folderStats'));
    }

    public function readAction()
    {
        $id = $this->getRequestID();
        $message = \R::findOne('mails_imap', 'message_id = ?', [$id]);

        if (!$message) {
            throw new \Exception('Письмо не найдено', 404);
        }

        \R::exec("UPDATE mails_imap SET is_seen = '1' WHERE message_id = ?", [$id]);
        $folderStats = $this->folderStats();
        $currentFolder = $this->folderByRawName((string)$message->folder);
        $mailbox = null;
        if ((string)$message->attachments === '1' && $currentFolder === 'inbox') {
            $mailbox = new SafeMailbox(
                '{' . App::$app->getProperty('imap_host') . ':' . App::$app->getProperty('imap_port') . '/imap/ssl}INBOX',
                App::$app->getProperty('imap_login'),
                App::$app->getProperty('imap_password'),
                false,
                'UTF-8'
            );
        }

        $this->setMeta('Чтение письма');
        $this->set(compact('message', 'mailbox', 'folderStats', 'currentFolder'));
    }

    public function testAction()
    {
        $this->setMeta('Входящие');
    }

    public function composeAction()
    {
        if (!empty($_POST)) {
            $mailbox = new Mails();
            $data = $_POST;
            $mailbox->load($data);
            $mailbox->mailboxEmail($data);
        }

        $folderStats = $this->folderStats();
        $currentFolder = 'Sent';
        $this->setMeta('Написать новое письмо');
        $this->set(compact('folderStats', 'currentFolder'));
    }

    public function answerAction()
    {
        if (!empty($_POST)) {
            $mailbox = new Mails();
            $data = $_POST;
            $mailbox->load($data);
            $mailbox->mailboxAnswerEmail($data);
        }

        $id = $_GET['id'] ?? null;
        $message = $id ? \R::findOne('mails_imap', 'message_id = ?', [$id]) : null;
        $namecomp = App::$app->getProperty('shop_name');
        $folderStats = $this->folderStats();
        $currentFolder = $message ? $this->folderByRawName((string)$message->folder) : 'inbox';

        $this->setMeta('Ответить на письмо');
        $this->set(compact('namecomp', 'message', 'folderStats', 'currentFolder'));
    }

    public function serverProcessingAction()
    {
        $request = $_GET;
        $currentFolder = $this->currentFolder();
        [$where, $params] = $this->folderWhere($currentFolder);

        if (isset($request['seen']) && (string)$request['seen'] === '0') {
            $where[] = "is_seen != '1'";
        }

        $baseWhereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $baseParams = $params;
        $search = trim((string)($request['search']['value'] ?? ''));
        if ($search !== '') {
            $where[] = "(from_mail LIKE ? OR from_name LIKE ? OR subject LIKE ?)";
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like);
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $columns = ['message_id', 'from_mail', 'subject', 'attachments', 'is_seen', 'date_dispatch', 'message_id'];
        $orderColumn = (int)($request['order'][0]['column'] ?? 0);
        $orderDir = strtolower((string)($request['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orderBy = $columns[$orderColumn] ?? 'message_id';
        $orderSql = in_array($orderBy, ['from_mail', 'subject', 'date_dispatch', 'message_id'], true)
            ? "`{$orderBy}` {$orderDir}"
            : '`message_id` DESC';

        $start = max(0, (int)($request['start'] ?? 0));
        $length = (int)($request['length'] ?? 50);
        $length = $length < 1 ? 50 : min($length, 100);

        $recordsTotal = (int)\R::getCell('SELECT COUNT(*) FROM mails_imap ' . $baseWhereSql, $baseParams);
        $recordsFiltered = $search === ''
            ? $recordsTotal
            : (int)\R::getCell('SELECT COUNT(*) FROM mails_imap ' . $whereSql, $params);
        $rows = \R::getAll(
            "SELECT message_id, from_mail, from_name, subject, attachments, is_seen, date_dispatch
             FROM mails_imap
             {$whereSql}
             ORDER BY {$orderSql}
             LIMIT {$start}, {$length}",
            $params
        );

        $data = [];
        foreach ($rows as $row) {
            $messageId = (int)$row['message_id'];
            $subject = htmlspecialchars((string)$row['subject'], ENT_QUOTES, 'UTF-8');
            $from = htmlspecialchars(trim((string)($row['from_name'] ?: $row['from_mail'])), ENT_QUOTES, 'UTF-8');
            $fromMail = htmlspecialchars((string)$row['from_mail'], ENT_QUOTES, 'UTF-8');
            $date = $this->formatDate((string)$row['date_dispatch']);
            $actions = '<div class="btn-group btn-group-sm">'
                . '<a class="btn btn-default" title="Открыть" href="' . ADMIN . '/mailbox/read?id=' . $messageId . '"><i class="fas fa-eye text-primary"></i></a>'
                . '<a class="btn btn-default delete" title="В удалённые" href="' . ADMIN . '/mailbox/delete?id=' . $messageId . '"><i class="far fa-trash-alt text-danger"></i></a>'
                . '</div>';

            $data[] = [
                '<input type="checkbox" class="mail-check" value="' . $messageId . '">',
                '<span class="d-block">' . $from . '</span><small class="text-muted">' . $fromMail . '</small>',
                '<a href="' . ADMIN . '/mailbox/read?id=' . $messageId . '">' . ($subject ?: '(без темы)') . '</a>',
                (string)$row['attachments'] === '1' ? '<i class="fas fa-paperclip"></i>' : '',
                (string)$row['is_seen'] === '1' ? '1' : '0',
                $date,
                $actions,
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw' => (int)($request['draw'] ?? 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        die;
    }

    public function bulkAction()
    {
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? []))));
        $action = (string)($_POST['action'] ?? '');

        if (!$ids || !in_array($action, ['seen', 'unseen', 'trash', 'restore', 'delete'], true)) {
            $_SESSION['error'] = 'Выберите письма и действие';
            redirect();
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($action === 'seen') {
            \R::exec("UPDATE mails_imap SET is_seen = '1' WHERE message_id IN ({$placeholders})", $ids);
        } elseif ($action === 'unseen') {
            \R::exec("UPDATE mails_imap SET is_seen = '0' WHERE message_id IN ({$placeholders})", $ids);
        } elseif ($action === 'trash') {
            \R::exec("UPDATE mails_imap SET folder = 'Trash', date_last_modified = ? WHERE message_id IN ({$placeholders})", array_merge([date('Y-m-d H:i:s')], $ids));
        } elseif ($action === 'restore') {
            \R::exec("UPDATE mails_imap SET folder = 'INBOX', date_last_modified = ? WHERE message_id IN ({$placeholders})", array_merge([date('Y-m-d H:i:s')], $ids));
        } elseif ($action === 'delete') {
            \R::exec("DELETE FROM mails_imap WHERE message_id IN ({$placeholders})", $ids);
        }

        AdminActivityLogger::admin(61, 'mails_imap', (int)$ids[0]);
        $_SESSION['success'] = 'Действие выполнено для писем: ' . count($ids);
        redirect();
    }

    public function purgeTrashAction()
    {
        \R::exec(
            "DELETE FROM mails_imap
             WHERE folder IN ('Trash', 'Deleted', 'INBOX.Trash', 'INBOX.Deleted')
               AND COALESCE(date_last_modified, date_dispatch) < DATE_SUB(NOW(), INTERVAL 1 YEAR)"
        );

        $_SESSION['success'] = 'Старые удалённые письма очищены';
        redirect(ADMIN . '/mailbox?folder=Trash');
    }

    public function deleteAction()
    {
        $id = $this->getRequestID();
        \R::exec(
            "UPDATE mails_imap SET folder = 'Trash', date_last_modified = ? WHERE message_id = ?",
            [date('Y-m-d H:i:s'), $id]
        );

        AdminActivityLogger::admin(61, 'mails_imap', (int)$id);
        $_SESSION['success'] = 'Письмо перемещено в удалённые';
        redirect();
    }

    private function currentFolder(): string
    {
        $folder = (string)($_GET['folder'] ?? 'inbox');
        return isset($this->folders[$folder]) ? $folder : 'inbox';
    }

    private function folderWhere(string $folder): array
    {
        $aliases = $this->folders[$folder]['aliases'];
        $where = ['folder IN (' . implode(',', array_fill(0, count($aliases), '?')) . ')'];
        return [$where, $aliases];
    }

    private function folderByRawName(string $rawFolder): string
    {
        foreach ($this->folders as $folder => $config) {
            if (in_array($rawFolder, $config['aliases'], true)) {
                return $folder;
            }
        }

        return 'inbox';
    }

    private function folderStats(): array
    {
        $stats = [];
        foreach ($this->folders as $folder => $config) {
            [$where, $params] = $this->folderWhere($folder);
            $whereSql = 'WHERE ' . implode(' AND ', $where);
            $stats[$folder] = [
                'total' => (int)\R::getCell("SELECT COUNT(*) FROM mails_imap {$whereSql}", $params),
                'unseen' => (int)\R::getCell("SELECT COUNT(*) FROM mails_imap {$whereSql} AND is_seen != '1'", $params),
            ];
        }

        return $stats;
    }

    private function formatDate(string $date): string
    {
        if (!$date || strtotime($date) === false) {
            return '';
        }

        $days = App::getPeriodMailbox($date, date('Y-m-d H:i:s'));
        if ($days > 0 || date('Y-m-d') !== date('Y-m-d', strtotime($date))) {
            return App::abbreviateddate(date('Y-m-d', strtotime($date)));
        }

        return date('H:i', strtotime($date));
    }
}
