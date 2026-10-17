<?php
namespace app\services;

class AvitoChatWorkspace
{
    public static function ensureSchema(): void
    {
        \R::exec('CREATE TABLE IF NOT EXISTS avito_chat_assignment (chat_key CHAR(64) PRIMARY KEY, user_id INT NULL, updated_by INT NOT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        \R::exec('CREATE TABLE IF NOT EXISTS avito_chat_note (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, chat_key CHAR(64) NOT NULL, author_id INT NOT NULL, body TEXT NOT NULL, created_at INT NOT NULL, INDEX(chat_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        \R::exec('CREATE TABLE IF NOT EXISTS avito_chat_template (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(120) NOT NULL, body TEXT NOT NULL, author_id INT NOT NULL, active TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    private static function key(string $chat): string
    {
        if ($chat === '' || strlen($chat) > 256 || preg_match('/[\x00-\x20\x7f]/', $chat)) throw new \InvalidArgumentException('Выберите диалог.');
        return hash('sha256', $chat);
    }

    public static function workspace(string $chat, int $actor): array
    {
        $key = self::key($chat);
        $managers = \R::getAll("SELECT u.id, u.name FROM user u INNER JOIN avito_chat_manager m ON m.user_id=u.id WHERE u.role IN ('admin','manager') ORDER BY u.name, u.id");
        $assignee = (int)\R::getCell('SELECT user_id FROM avito_chat_assignment WHERE chat_key=?', [$key]);
        $notes = \R::getAll('SELECT n.id, n.body, n.created_at, u.name AS author FROM avito_chat_note n LEFT JOIN user u ON u.id=n.author_id WHERE n.chat_key=? ORDER BY n.id DESC LIMIT 100', [$key]);
        $templates = \R::getAll('SELECT id, title, body, author_id FROM avito_chat_template WHERE active=1 ORDER BY title, id');
        $admin = \R::getCell('SELECT role FROM user WHERE id=?', [$actor]) === 'admin';
        foreach ($templates as &$template) $template['can_edit'] = $admin || (int)$template['author_id'] === $actor;
        unset($template);
        return ['managers'=>$managers,'assignee'=>$assignee,'notes'=>array_reverse($notes),'templates'=>$templates];
    }

    public static function decorateChats(array $response): array
    {
        $keys = [];
        foreach ($response['chats'] ?? [] as $chat) $keys[] = self::key((string)$chat['id']);
        if (!$keys) return $response;
        $marks = implode(',',array_fill(0,count($keys),'?'));
        $assignments = \R::getAll("SELECT a.chat_key, u.name FROM avito_chat_assignment a LEFT JOIN user u ON u.id=a.user_id WHERE a.user_id IS NOT NULL AND a.chat_key IN ($marks)",$keys);
        $names = [];
        foreach ($assignments as $assignment) $names[$assignment['chat_key']] = $assignment['name'] ?: 'Сотрудник недоступен';
        foreach ($response['chats'] as &$chat) $chat['assigned_to'] = $names[self::key((string)$chat['id'])] ?? '';
        unset($chat);
        return $response;
    }

    public static function assign(string $chat, int $manager, int $actor): void
    {
        $key = self::key($chat);
        if ($manager < 0 || ($manager > 0 && !AvitoMessenger::allowed($manager))) throw new \InvalidArgumentException('У сотрудника нет доступа к чату Авито.');
        // Single row, concurrent assignments are serialized by the primary key.
        \R::exec('REPLACE INTO avito_chat_assignment (chat_key,user_id,updated_by) VALUES (?,?,?)', [$key,$manager ?: null,$actor]);
    }

    public static function addNote(string $chat, string $body, int $actor): void
    {
        $key = self::key($chat); $body = trim($body);
        if ($body === '' || mb_strlen($body,'UTF-8') > 5000) throw new \InvalidArgumentException('Заметка должна содержать от 1 до 5000 символов.');
        \R::exec('INSERT INTO avito_chat_note (chat_key,author_id,body,created_at) VALUES (?,?,?,?)', [$key,$actor,$body,time()]);
    }

    private static function mayEdit(int $id, int $actor): void
    {
        if (!\R::getCell("SELECT t.id FROM avito_chat_template t INNER JOIN user u ON u.id=? WHERE t.id=? AND t.active=1 AND (t.author_id=u.id OR u.role='admin')", [$actor,$id])) {
            throw new \InvalidArgumentException('Изменять шаблон может его автор или администратор.');
        }
    }

    public static function saveTemplate(int $id, string $title, string $body, int $actor): void
    {
        $title=trim($title); $body=trim($body);
        if ($id < 0 || $title === '' || mb_strlen($title,'UTF-8') > 120 || $body === '' || mb_strlen($body,'UTF-8') > 1000) {
            throw new \InvalidArgumentException('Укажите название до 120 символов и ответ до 1000 символов.');
        }
        if ($id) {
            self::mayEdit($id,$actor);
            \R::exec('UPDATE avito_chat_template SET title=?,body=? WHERE id=?',[$title,$body,$id]);
        } else \R::exec('INSERT INTO avito_chat_template (title,body,author_id) VALUES (?,?,?)',[$title,$body,$actor]);
    }

    public static function deleteTemplate(int $id, int $actor): void
    {
        self::mayEdit($id,$actor);
        \R::exec('UPDATE avito_chat_template SET active=0 WHERE id=?',[$id]);
    }
}
