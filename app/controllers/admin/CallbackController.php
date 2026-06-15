<?php

namespace app\controllers\admin;

use app\services\admin\AdminActivityLogger;

class CallbackController extends AppController
{
    public function indexAction()
    {
        $where = [];
        $params = [];
        $filter = (string)($_GET['filter'] ?? '');
        $type = (string)($_GET['type'] ?? '');

        if ($filter === 'new') {
            $where[] = "c.hide = 'show'";
            $where[] = "c.status = '0'";
        }

        if ($type === 'catalog') {
            $where[] = "c.topic LIKE ?";
            $params[] = '%каталог%';
        } elseif ($type === 'callback') {
            $where[] = "(c.topic IS NULL OR c.topic NOT LIKE ?)";
            $params[] = '%каталог%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $callback = \R::getAll(
            "SELECT c.*, u.name AS user_name
             FROM callback c
             LEFT JOIN user u ON u.id = c.user_id
             {$whereSql}
             ORDER BY c.date_create DESC",
            $params
        );

        $this->setMeta('Обратные звонки');
        $this->set(compact('callback'));
    }

    public function viewAction()
    {
        $id = $this->getRequestID();
        $item = \R::getRow(
            "SELECT c.*, u.name AS user_name, u.email AS user_email
             FROM callback c
             LEFT JOIN user u ON u.id = c.user_id
             WHERE c.id = ?
             LIMIT 1",
            [$id]
        );

        if (!$item) {
            throw new \Exception('Заявка не найдена', 404);
        }

        $this->setMeta('Заявка на обратный звонок');
        $this->set(compact('item'));
    }

    public function processAction()
    {
        $id = $this->getRequestID();
        \R::exec(
            "UPDATE callback
             SET status = '1', user_modified = ?, date_modified = ?
             WHERE id = ?",
            [$_SESSION['user']['id'] ?? '', date('Y-m-d H:i:s'), $id]
        );
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_PROCESS, 'callback', (int)$id);
        $_SESSION['success'] = 'Заявка взята в обработку';
        redirect(ADMIN . '/callback');
    }

    public function doneAction()
    {
        $id = $this->getRequestID();
        \R::exec(
            "UPDATE callback
             SET status = '2', hide = 'hide', user_modified = ?, date_modified = ?
             WHERE id = ?",
            [$_SESSION['user']['id'] ?? '', date('Y-m-d H:i:s'), $id]
        );
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_DONE, 'callback', (int)$id);
        $_SESSION['success'] = 'Заявка обработана';
        redirect(ADMIN . '/callback');
    }

    public function deleteAction()
    {
        $id = $this->getRequestID();
        \R::exec(
            "UPDATE callback
             SET status = '2', hide = 'hide', user_modified = ?, date_modified = ?
             WHERE id = ?",
            [$_SESSION['user']['id'] ?? '', date('Y-m-d H:i:s'), $id]
        );
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_CLOSE, 'callback', (int)$id);
        $_SESSION['success'] = 'Заявка закрыта';
        redirect(ADMIN . '/callback');
    }
}
