<?php

namespace app\controllers\admin;

class SearchController extends AppController
{
    public function indexAction()
    {
        $query = trim((string)($_GET['q'] ?? ''));
        $results = $query !== '' ? $this->search($query, 100) : [];

        $this->setMeta('Поиск');
        $this->set(compact('query', 'results'));
    }

    public function typeaheadAction()
    {
        if ($this->isAjax()) {
            $query = trim((string)($_GET['query'] ?? $_GET['q'] ?? ''));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($query !== '' ? $this->search($query, 15) : [], JSON_UNESCAPED_UNICODE);
        }
        die;
    }

    private function search(string $query, int $limit): array
    {
        $limit = max(1, min(100, (int)$limit));
        $like = '%' . $query . '%';
        $numericId = (int)preg_replace('/\D+/', '', $query);
        $items = [];

        $this->addRows($items, 'Товар', \R::getAll(
            "SELECT id, name, article, price, CONCAT('/admin/product/edit?id=', id) AS url
             FROM product
             WHERE CONCAT_WS(' ', name, article, sku, model) LIKE ?
             ORDER BY id DESC
             LIMIT {$limit}",
            [$like]
        ), static fn($r) => trim(($r['article'] ? $r['article'] . ' - ' : '') . $r['name']), static fn($r) => 'ID ' . $r['id'] . ((string)$r['price'] !== '' ? ' / ' . number_format((float)$r['price'], 0, '.', ' ') . ' руб.' : ''));

        $this->addRows($items, 'Техника', \R::getAll(
            "SELECT id, model AS name, title, CONCAT('/admin/plagins/technics-edit?id=', id) AS url
             FROM technics
             WHERE model LIKE ? OR title LIKE ?
             ORDER BY id DESC
             LIMIT {$limit}",
            [$like, $like]
        ), null, static fn($r) => trim('ID ' . $r['id'] . ($r['title'] ? ' / ' . $r['title'] : '')));

        $this->addRows($items, 'Пользователь', \R::getAll(
            "SELECT id, name, email, telefon, CONCAT('/admin/user/edit?id=', id) AS url
             FROM user
             WHERE CONCAT_WS(' ', name, email, telefon) LIKE ?
             ORDER BY id DESC
             LIMIT {$limit}",
            [$like]
        ), static fn($r) => trim($r['name'] . ' ' . $r['email']), static fn($r) => trim('ID ' . $r['id'] . ($r['telefon'] ? ' / ' . $r['telefon'] : '')));

        $this->addRows($items, 'Компания', \R::getAll(
            "SELECT id, comp_name AS name, inn, user_id, CONCAT('/admin/company/edit?id=', id) AS url
             FROM company
             WHERE CONCAT_WS(' ', comp_name, comp_short_name, inn) LIKE ?
             ORDER BY id DESC
             LIMIT {$limit}",
            [$like]
        ), static fn($r) => trim($r['name'] . ($r['inn'] ? ' ИНН ' . $r['inn'] : '')), static fn($r) => 'ID ' . $r['id'] . ($r['user_id'] ? ' / user #' . $r['user_id'] : ''));

        $this->addRows($items, 'Заказ', \R::getAll(
            "SELECT o.id, o.inv, o.date, o.status, u.name AS user_name, u.email, u.telefon, c.comp_name, CONCAT('/admin/order/view?id=', o.id) AS url
             FROM `order` o
             LEFT JOIN user u ON u.id = o.user_id
             LEFT JOIN company c ON c.id = o.comp_id
             WHERE o.id = ?
                OR o.inv LIKE ?
                OR CONCAT_WS(' ', u.name, u.email, u.telefon, c.comp_name) LIKE ?
             ORDER BY o.id DESC
             LIMIT {$limit}",
            [$numericId, $like, $like]
        ), static fn($r) => 'Заказ #' . ($r['inv'] ?: $r['id']), static fn($r) => trim(($r['user_name'] ?: $r['comp_name'] ?: 'Клиент не указан') . ($r['date'] ? ' / ' . date('d.m.Y H:i', strtotime($r['date'])) : '')));

        $this->addRows($items, 'Заказ в 1 клик', \R::getAll(
            "SELECT mo.id, mo.product_id, mo.fio_click, mo.tell_click, mo.email_click, mo.data_create, p.name AS product_name, CONCAT('/admin/oneclick') AS url
             FROM mail_oneclick mo
             LEFT JOIN product p ON p.id = mo.product_id
             WHERE mo.id = ?
                OR CONCAT_WS(' ', mo.fio_click, mo.tell_click, mo.email_click, mo.prim_click, p.name) LIKE ?
             ORDER BY mo.data_create DESC
             LIMIT {$limit}",
            [$numericId, $like]
        ), static fn($r) => trim(($r['fio_click'] ?: $r['tell_click'] ?: 'Заявка') . ($r['product_name'] ? ' - ' . $r['product_name'] : '')), static fn($r) => trim(($r['tell_click'] ?: $r['email_click']) . ($r['data_create'] ? ' / ' . date('d.m.Y H:i', strtotime($r['data_create'])) : '')));

        $this->addRows($items, 'Запрос товара', \R::getAll(
            "SELECT mr.id, mr.product_id, mr.fio, mr.tell, mr.email, mr.data_create, p.name AS product_name, CONCAT('/admin/request') AS url
             FROM mail_request mr
             LEFT JOIN product p ON p.id = mr.product_id
             WHERE mr.id = ?
                OR CONCAT_WS(' ', mr.fio, mr.tell, mr.email, mr.note, p.name) LIKE ?
             ORDER BY mr.data_create DESC
             LIMIT {$limit}",
            [$numericId, $like]
        ), static fn($r) => trim(($r['fio'] ?: $r['tell'] ?: 'Заявка') . ($r['product_name'] ? ' - ' . $r['product_name'] : '')), static fn($r) => trim(($r['tell'] ?: $r['email']) . ($r['data_create'] ? ' / ' . date('d.m.Y H:i', strtotime($r['data_create'])) : '')));

        $this->addRows($items, 'Обратный звонок', \R::getAll(
            "SELECT id, topic AS name, phone, date_create, CONCAT('/admin/callback/view?id=', id) AS url
             FROM callback
             WHERE id = ?
                OR CONCAT_WS(' ', topic, phone) LIKE ?
             ORDER BY date_create DESC
             LIMIT {$limit}",
            [$numericId, $like]
        ), static fn($r) => trim($r['name'] ?: $r['phone'] ?: 'Заявка'), static fn($r) => trim($r['phone'] . ($r['date_create'] ? ' / ' . date('d.m.Y H:i', strtotime($r['date_create'])) : '')));

        return array_slice($items, 0, $limit);
    }

    private function addRows(array &$items, string $type, array $rows, ?callable $label = null, ?callable $subtitle = null): void
    {
        foreach ($rows as $row) {
            $name = $label ? $label($row) : ($row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $items[] = [
                'id' => (int)$row['id'],
                'name' => $name,
                'type' => $type,
                'subtitle' => $subtitle ? trim((string)$subtitle($row)) : '',
                'url' => $row['url'],
            ];
        }
    }
}
