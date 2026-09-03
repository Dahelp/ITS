<?php

namespace app\controllers\admin;

use app\models\admin\Certificate;
use app\services\admin\AdminActivityLogger;

class CertificateController extends AppController
{
    public function indexAction()
    {
        $certificates = \R::getAll(
            "SELECT c.*, COUNT(ca.id) AS assignments_count
             FROM certificates c LEFT JOIN certificate_assignments ca ON ca.certificate_id = c.id
             GROUP BY c.id ORDER BY c.status = 'active' DESC, c.date_end, c.number"
        );
        $this->setMeta('Сертификаты и декларации');
        $this->set(compact('certificates'));
    }

    public function addAction()
    {
        if (!empty($_POST)) {
            $model = new Certificate();
            $data = $this->normalise($_POST);
            $model->load($data);
            if (!$model->validate($data)) {
                $model->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }
            if ($id = $model->save('certificates')) {
                Certificate::syncAssignments((int)$id, $data);
                AdminActivityLogger::admin(4, 'certificates', (int)$id);
                $_SESSION['success'] = 'Документ добавлен';
            }
            redirect(ADMIN . '/certificate');
        }
        $certificate = \R::dispense('certificates');
        $certificate->document_type = 'declaration';
        $certificate->status = 'active';
        $assignments = [];
        $this->formData($certificate, $assignments, 'Новый документ');
    }

    public function editAction()
    {
        if (!empty($_POST)) {
            $id = $this->getRequestID(false);
            $model = new Certificate();
            $data = $this->normalise($_POST);
            $model->load($data);
            if (!$model->validate($data)) {
                $model->getErrors();
                redirect();
            }
            if ($model->update('certificates', $id)) {
                Certificate::syncAssignments($id, $data);
                AdminActivityLogger::admin(5, 'certificates', $id);
                $_SESSION['success'] = 'Документ и назначения сохранены';
            }
            redirect(ADMIN . '/certificate/edit?id=' . $id);
        }
        $id = $this->getRequestID();
        $certificate = \R::load('certificates', $id);
        if (!$certificate->id) {
            throw new \Exception('Документ не найден', 404);
        }
        $assignments = \R::getAll('SELECT * FROM certificate_assignments WHERE certificate_id = ? ORDER BY id', [$id]);
        $this->formData($certificate, $assignments, 'Редактирование документа');
    }

    public function deleteAction()
    {
        $id = $this->getRequestID();
        $document = \R::load('certificates', $id);
        if ($document->id) {
            $document->status = 'archived';
            $document->updated_at = date('Y-m-d H:i:s');
            \R::store($document);
            AdminActivityLogger::admin(5, 'certificates', $id);
            $_SESSION['success'] = 'Документ перемещён в архив; история назначений сохранена';
        }
        redirect(ADMIN . '/certificate');
    }

    private function normalise(array $data): array
    {
        foreach (['date_start', 'date_end'] as $field) {
            $data[$field] = trim((string)($data[$field] ?? '')) ?: null;
        }
        $data['registry_url'] = trim((string)($data['registry_url'] ?? ''));
        $data['file_url'] = trim((string)($data['file_url'] ?? ''));
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    private function formData($certificate, array $assignments, string $title): void
    {
        $categories = \R::getAll('SELECT id, name FROM category ORDER BY name');
        $brands = \R::getAll('SELECT id, name FROM brand ORDER BY name');
        $products = \R::getAll('SELECT id, article, name FROM product ORDER BY name');
        $this->setMeta($title);
        $this->set(compact('certificate', 'assignments', 'categories', 'brands', 'products'));
    }
}
