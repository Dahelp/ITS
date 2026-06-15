<?php

namespace app\controllers\admin;

use app\models\admin\FiltrsAttr;
use app\models\admin\FiltrsGroup;
use app\services\admin\AdminActivityLogger;
use app\models\AppModel;
use ishop\App;

class FiltrsController extends AppController
{
    public function groupDeleteAction()
    {
        $id = $this->getRequestID();

        $count = \R::count('attribute_value', 'attr_group_id = ?', [$id]);
        if ($count) {
            $_SESSION['error'] = 'Удаление невозможно, в группе есть атрибуты';
            redirect();
        }

        $group = \R::findOne('attribute_group', 'id = ?', [$id]);

        if ($group && !empty($group['url_params'])) {
            $fileName = $this->buildControllerNameFromUrlParams((string)$group['url_params']);

            @unlink(APP . "/controllers/{$fileName}Controller.php");

            $dir = APP . "/views/" . TEMPLATE . "/{$fileName}";
            if (file_exists($dir) && is_dir($dir)) {
                chmod($dir, 0777);
                if ($elements = glob($dir . "/*")) {
                    foreach ($elements as $element) {
                        is_dir($element) ? removeDirectory($element) : unlink($element);
                    }
                }
                @rmdir($dir);
            }

            $dirRoute = CONF . '/routes.php';
            $fileSource = file_get_contents($dirRoute);
            if ($fileSource !== false) {
                $fileSource = preg_replace(
                    "#\n//" . preg_quote($fileName, '#') . "//.*?//And" . preg_quote($fileName, '#') . "//#is",
                    '',
                    $fileSource
                );
                file_put_contents($dirRoute, $fileSource);
            }
        }

        \R::exec('DELETE FROM attribute_group WHERE id = ?', [$id]);
        \R::exec('DELETE FROM attribute_category WHERE group_id = ?', [$id]);

        AdminActivityLogger::admin(30, 'attribute_group', (int)$id);

        $_SESSION['success'] = 'Удалено';
        redirect();
    }

    public function addImageAction()
    {
        if (isset($_GET['upload'])) {
            if ($_POST['name'] == 'single') {
                $wmax = App::$app->getProperty('img_width_filter');
                $hmax = App::$app->getProperty('img_height_filter');
            }

            $name = $_POST['name'];
            $attr = new FiltrsAttr();
            $attr->uploadImg($name, $wmax, $hmax);
        }
    }

    public function addCategorySeoImageAction()
    {
        if (empty($_FILES['category_seo_img'])) {
            exit(json_encode(['error' => 'Файл не получен']));
        }

        $uploaddir = WWW . '/images/filtrs/baseimg/';

        if (!is_dir($uploaddir)) {
            @mkdir($uploaddir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['category_seo_img']['name'], PATHINFO_EXTENSION));

        $types = [
            'image/gif',
            'image/png',
            'image/jpeg',
            'image/pjpeg',
            'image/x-png',
            'image/webp',
        ];

        if ($_FILES['category_seo_img']['size'] > 1048576) {
            exit(json_encode(['error' => 'Ошибка! Максимальный вес файла - 1 Мб!']));
        }

        if ($_FILES['category_seo_img']['error']) {
            exit(json_encode(['error' => 'Ошибка! Возможно, файл слишком большой.']));
        }

        if (!in_array($_FILES['category_seo_img']['type'], $types, true)) {
            exit(json_encode(['error' => 'Допустимые расширения - .gif, .jpg, .png, .webp']));
        }

        $newName = md5(time() . mt_rand(1000, 9999)) . '.' . $ext;
        $uploadfile = $uploaddir . $newName;

        if (@move_uploaded_file($_FILES['category_seo_img']['tmp_name'], $uploadfile)) {
            $wmax = App::$app->getProperty('img_width_filter');
            $hmax = App::$app->getProperty('img_height_filter');

            if (method_exists(FiltrsAttr::class, 'resize')) {
                FiltrsAttr::resize($uploadfile, $uploadfile, $wmax, $hmax, $ext);
            }

            exit(json_encode(['file' => $newName]));
        }

        exit(json_encode(['error' => 'Не удалось загрузить файл']));
    }

    public function attributeDeleteAction()
    {
        $id = $this->getRequestID();

        \R::exec("DELETE FROM attribute_product WHERE attr_id = ?", [$id]);
        \R::exec("DELETE FROM attribute_value_related WHERE attr_value_id = ? OR related_attr_value_id = ?", [$id, $id]);
        \R::exec("DELETE FROM attribute_value_technic WHERE attr_value_id = ?", [$id]);
        \R::exec("DELETE FROM attribute_value_faq WHERE attr_value_id = ?", [$id]);
        \R::exec("DELETE FROM attribute_value_category_faq WHERE attr_value_id = ?", [$id]);
        \R::exec("DELETE FROM attribute_value_category_canonical WHERE attr_value_id = ?", [$id]);
        \R::exec("DELETE FROM attribute_value WHERE id = ?", [$id]);

        $_SESSION['success'] = 'Удалено';
        redirect();
    }

    public function attributeEditAction()
    {
        $this->ensureCategorySeoSchema();
        $categorySeoColumns = $this->getCategorySeoColumns();

        if (!empty($_POST)) {
            $id = $this->getRequestID(false);

            $attr = new FiltrsAttr();
            $data = $_POST;

            $postAlias = trim($data['alias'] ?? '');

            unset($data['alias']);
            unset($data['category_seo']);
            unset($data['related_sizes']);
            unset($data['technic_ids']);

            unset($attr->attributes['alias']);

            // Старые SEO-поля больше не редактируются здесь.
            // Оставляем их в БД как резерв, но не трогаем при сохранении фильтра.
            unset($attr->attributes['content']);
            unset($attr->attributes['top_content']);
            unset($attr->attributes['seo_h1']);
            unset($attr->attributes['title']);
            unset($attr->attributes['description']);
            unset($attr->attributes['keywords']);
            unset($attr->attributes['img']);

            $attr->load($data);

            if (!$attr->validate($data)) {
                $attr->getErrors();
                redirect();
            }

            if ($attr->update('attribute_value', $id)) {

                // Alias фильтра
                if ($postAlias !== '') {
                    $bean = \R::load('attribute_value', $id);

                    $allowDot = (strpos($postAlias, '.') !== false);
                    $allowSlash = (strpos($postAlias, '/') !== false);

                    $newAlias = AppModel::createAlias(
                        'attribute_value',
                        'alias',
                        $postAlias,
                        $id,
                        $allowDot,
                        $allowSlash
                    );

                    if ($newAlias !== $bean->alias) {
                        $bean->alias = $newAlias;
                        \R::store($bean);
                    }
                }

                // 1) Связанные типоразмеры
                $related = $_POST['related_sizes'] ?? [];
                $related = array_values(array_unique(array_filter(array_map('intval', (array)$related))));

                \R::exec("DELETE FROM attribute_value_related WHERE attr_value_id = ?", [$id]);

                foreach ($related as $rid) {
                    if ($rid == $id) {
                        continue;
                    }

                    \R::exec(
                        "INSERT IGNORE INTO attribute_value_related
                        (attr_value_id, related_attr_value_id, sort)
                        VALUES (?, ?, 500)",
                        [$id, $rid]
                    );
                }

                // 2) Техника
                $technicIds = $_POST['technic_ids'] ?? [];
                $technicIds = array_values(array_unique(array_filter(array_map('intval', (array)$technicIds))));

                \R::exec("DELETE FROM attribute_value_technic WHERE attr_value_id = ?", [$id]);

                foreach ($technicIds as $tid) {
                    \R::exec(
                        "INSERT IGNORE INTO attribute_value_technic
                        (attr_value_id, technic_id, sort)
                        VALUES (?, ?, 500)",
                        [$id, $tid]
                    );
                }

                // Удаление SEO-страниц фильтра по категориям,
                // которые пользователь убрал кнопкой "Убрать из формы"
                $deleteCategorySeo = $_POST['delete_category_seo'] ?? [];
                $deleteCategorySeo = array_values(array_unique(array_filter(array_map('intval', (array)$deleteCategorySeo))));

                if (!empty($deleteCategorySeo)) {
                    foreach ($deleteCategorySeo as $ruleId) {
                        $rule = \R::getRow(
                            "SELECT id, category_id
                            FROM attribute_value_category_canonical
                            WHERE id = ?
                            AND attr_value_id = ?
                            LIMIT 1",
                            [$ruleId, $id]
                        );

                        if (empty($rule)) {
                            continue;
                        }

                        \R::exec(
                            "DELETE FROM attribute_value_category_faq
                            WHERE attr_value_id = ?
                            AND category_id = ?",
                            [$id, (int)$rule['category_id']]
                        );

                        \R::exec(
                            "DELETE FROM attribute_value_category_canonical
                            WHERE id = ?
                            AND attr_value_id = ?",
                            [$ruleId, $id]
                        );
                    }
                }

                // 3) SEO-страницы фильтра по категориям + FAQ каждой категории
                $categorySeo = $_POST['category_seo'] ?? [];

                if (!empty($categorySeo) && is_array($categorySeo)) {
                    $seenCategoryIds = [];

                    foreach ($categorySeo as $row) {
                        if (!is_array($row)) {
                            continue;
                        }

                        $catId = (int)($row['category_id'] ?? 0);
                        if ($catId <= 0) {
                            continue;
                        }

                        if (isset($seenCategoryIds[$catId])) {
                            $_SESSION['error'] = 'Дубль SEO-страницы: для одной категории и одного фильтра может быть только одна запись.';
                            redirect();
                        }
                        $seenCategoryIds[$catId] = true;

                        $isActive = !empty($row['is_active']) ? 1 : 0;

                        $mode = (string)($row['mode'] ?? 'landing');
                        if (!in_array($mode, ['landing', 'redirect'], true)) {
                            $mode = 'landing';
                        }

                        $redirectCategoryId = !empty($row['redirect_category_id'])
                            ? (int)$row['redirect_category_id']
                            : null;

                        if ($mode === 'redirect' && empty($redirectCategoryId)) {
                            $_SESSION['error'] = 'Для режима Redirect нужно выбрать категорию редиректа.';
                            redirect();
                        }

                        if ($redirectCategoryId && $redirectCategoryId === $catId) {
                            $_SESSION['error'] = 'Категория редиректа не должна совпадать с исходной SEO-страницей.';
                            redirect();
                        }

                        $seoH1       = trim((string)($row['seo_h1'] ?? ''));
                        $title       = trim((string)($row['title'] ?? ''));
                        $description = trim((string)($row['description'] ?? ''));
                        $keywords    = trim((string)($row['keywords'] ?? ''));
                        $topContent  = trim((string)($row['top_content'] ?? ''));
                        $content     = trim((string)($row['content'] ?? ''));
                        $img         = trim((string)($row['img'] ?? ''));
                        $canonicalUrl = isset($categorySeoColumns['canonical_url'])
                            ? trim((string)($row['canonical_url'] ?? ''))
                            : '';
                        $robots = isset($categorySeoColumns['robots'])
                            ? trim((string)($row['robots'] ?? ''))
                            : '';

                        $existsId = (int)\R::getCell(
                            "SELECT id
                            FROM attribute_value_category_canonical
                            WHERE attr_value_id = ?
                            AND category_id = ?
                            LIMIT 1",
                            [$id, $catId]
                        );

                        $hasData = (
                            $isActive === 1 ||
                            $mode === 'redirect' ||
                            $redirectCategoryId ||
                            $seoH1 !== '' ||
                            $title !== '' ||
                            $description !== '' ||
                            $keywords !== '' ||
                            $topContent !== '' ||
                            $content !== '' ||
                            $img !== '' ||
                            $canonicalUrl !== '' ||
                            $robots !== ''
                        );

                        if (!$existsId && !$hasData) {
                            continue;
                        }

                        if ($existsId) {
                            $setParts = [
                                'is_active = ?',
                                'mode = ?',
                                'redirect_category_id = ?',
                                'seo_h1 = ?',
                                'title = ?',
                                'description = ?',
                                'keywords = ?',
                                'top_content = ?',
                                'content = ?',
                                'img = ?',
                            ];
                            $values = [
                                $isActive,
                                $mode,
                                $redirectCategoryId,
                                $seoH1,
                                $title,
                                $description,
                                $keywords,
                                $topContent,
                                $content,
                                $img,
                            ];

                            if (isset($categorySeoColumns['canonical_url'])) {
                                $setParts[] = 'canonical_url = ?';
                                $values[] = $canonicalUrl;
                            }

                            if (isset($categorySeoColumns['robots'])) {
                                $setParts[] = 'robots = ?';
                                $values[] = $robots;
                            }

                            if (isset($categorySeoColumns['updated_at'])) {
                                $setParts[] = 'updated_at = ?';
                                $values[] = date('Y-m-d H:i:s');
                            }

                            $values[] = $existsId;

                            \R::exec(
                                "UPDATE attribute_value_category_canonical
                                SET " . implode(",\n                                    ", $setParts) . "
                                WHERE id = ?",
                                $values
                            );
                        } else {
                            $columns = [
                                'attr_value_id',
                                'category_id',
                                'is_active',
                                'mode',
                                'source',
                                'redirect_category_id',
                                'seo_h1',
                                'title',
                                'description',
                                'keywords',
                                'top_content',
                                'content',
                                'img',
                            ];
                            $placeholders = ['?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?'];
                            $values = [
                                $id,
                                $catId,
                                $isActive,
                                $mode,
                                'manual',
                                $redirectCategoryId,
                                $seoH1,
                                $title,
                                $description,
                                $keywords,
                                $topContent,
                                $content,
                                $img,
                            ];

                            if (isset($categorySeoColumns['canonical_url'])) {
                                $columns[] = 'canonical_url';
                                $placeholders[] = '?';
                                $values[] = $canonicalUrl;
                            }

                            if (isset($categorySeoColumns['robots'])) {
                                $columns[] = 'robots';
                                $placeholders[] = '?';
                                $values[] = $robots;
                            }

                            if (isset($categorySeoColumns['created_at'])) {
                                $columns[] = 'created_at';
                                $placeholders[] = '?';
                                $values[] = date('Y-m-d H:i:s');
                            }

                            if (isset($categorySeoColumns['updated_at'])) {
                                $columns[] = 'updated_at';
                                $placeholders[] = '?';
                                $values[] = date('Y-m-d H:i:s');
                            }

                            \R::exec(
                                "INSERT INTO attribute_value_category_canonical
                                (" . implode(', ', $columns) . ")
                                VALUES (" . implode(', ', $placeholders) . ")",
                                $values
                            );
                        }

                        // FAQ конкретной SEO-категории
                        $faq = $row['faq'] ?? [];

                        \R::exec(
                            "DELETE FROM attribute_value_category_faq
                            WHERE attr_value_id = ?
                            AND category_id = ?",
                            [$id, $catId]
                        );

                        if (!empty($faq) && is_array($faq)) {
                            $q = $faq['q'] ?? [];
                            $a = $faq['a'] ?? [];
                            $s = $faq['s'] ?? [];
                            $h = $faq['h'] ?? [];

                            $cnt = max(count($q), count($a));

                            for ($i = 0; $i < $cnt; $i++) {
                                $question = trim((string)($q[$i] ?? ''));
                                $answer   = trim((string)($a[$i] ?? ''));

                                if ($question === '' || $answer === '') {
                                    continue;
                                }

                                $sort = (int)($s[$i] ?? 500);
                                $hide = (($h[$i] ?? 'show') === 'hide') ? 'hide' : 'show';

                                \R::exec(
                                    "INSERT INTO attribute_value_category_faq
                                    (attr_value_id, category_id, question, answer, sort, hide)
                                    VALUES (?, ?, ?, ?, ?, ?)",
                                    [$id, $catId, $question, $answer, $sort, $hide]
                                );
                            }
                        }
                    }
                }

                $_SESSION['success'] = 'Изменения сохранены';
                redirect();
            }
        }

        $id = $this->getRequestID();

        $attr = \R::load('attribute_value', $id);
        if (!$attr || empty($attr->id)) {
            throw new \Exception('Фильтр не найден', 404);
        }

        $attrs_group = \R::findAll('attribute_group');
        $attrs = \R::getCell(
            "SELECT url_params FROM attribute_group WHERE id = ?",
            [$attr->attr_group_id]
        );

        $sizeGroup = \R::findOne('attribute_group', 'url_params = ?', ['size']);
        $sizeValues = $sizeGroup
            ? \R::findAll('attribute_value', 'attr_group_id = ? ORDER BY value', [$sizeGroup->id])
            : [];

        $relatedSizeIds = \R::getCol(
            "SELECT related_attr_value_id
            FROM attribute_value_related
            WHERE attr_value_id = ?
            ORDER BY sort, id",
            [$attr->id]
        );

        $technics = \R::findAll('technics_type', "hide = 'show' ORDER BY name");

        $technicIds = \R::getCol(
            "SELECT technic_id
            FROM attribute_value_technic
            WHERE attr_value_id = ?
            ORDER BY sort, id",
            [$attr->id]
        );

        $canonicalCategories = \R::findAll('category', 'ORDER BY name');

        $availableSeoCategories = \R::getAll(
            "SELECT id, name, alias
            FROM category
            ORDER BY name"
        );

        $seoExtraSelect = '';
        foreach (['canonical_url', 'robots', 'created_at', 'updated_at'] as $optionalColumn) {
            if (isset($categorySeoColumns[$optionalColumn])) {
                $seoExtraSelect .= ",\n                avcc.{$optionalColumn}";
            }
        }

        $seoCategories = \R::getAll(
            "SELECT
                c.id,
                c.name,
                c.alias,
                avcc.id AS rule_id,
                avcc.is_active,
                avcc.mode,
                avcc.source,
                avcc.redirect_category_id,
                avcc.seo_h1,
                avcc.title,
                avcc.description,
                avcc.keywords,
                avcc.top_content,
                avcc.content,
                avcc.img
                {$seoExtraSelect}
            FROM attribute_value_category_canonical avcc
            JOIN category c ON c.id = avcc.category_id
            WHERE avcc.attr_value_id = ?
            ORDER BY
                CASE WHEN avcc.source = 'auto' THEN 0 ELSE 1 END,
                c.name",
            [$attr->id]
        );

        if (!empty($seoCategories)) {
            foreach ($seoCategories as &$seoRow) {
                $seoRow['faq'] = \R::getAll(
                    "SELECT id, question, answer, sort, hide
                    FROM attribute_value_category_faq
                    WHERE attr_value_id = ?
                    AND category_id = ?
                    ORDER BY sort, id",
                    [(int)$attr->id, (int)$seoRow['id']]
                );
            }
            unset($seoRow);
        }

        $this->setMeta('Редактирование атрибута');

        $this->set(compact(
            'attr',
            'attrs_group',
            'attrs',
            'sizeValues',
            'relatedSizeIds',
            'technics',
            'technicIds',
            'canonicalCategories',
            'availableSeoCategories',
            'seoCategories'
        ));
    }

    public function attributeAddAction()
    {
        if (!empty($_POST)) {
            $attr = new FiltrsAttr();
            $data = $_POST;

            $attr->load($data);
            $attr->getImg();

            if (!$attr->validate($data) || !$attr->checkUnique()) {
                $attr->getErrors();
                redirect();
            }

            if ($id = $attr->save('attribute_value', false)) {
                $bean = \R::load('attribute_value', $id);

                $source = trim($data['alias'] ?? '') !== '' ? $data['alias'] : $data['value'];

                $allowDot = (strpos($source, '.') !== false);
                $allowSlash = (strpos($source, '/') !== false);

                $bean->alias = AppModel::createAlias(
                    'attribute_value',
                    'alias',
                    $source,
                    $id,
                    $allowDot,
                    $allowSlash
                );

                \R::store($bean);

                $related = $_POST['related_sizes'] ?? [];
                $related = array_values(array_unique(array_filter(array_map('intval', (array)$related))));

                \R::exec("DELETE FROM attribute_value_related WHERE attr_value_id = ?", [$id]);

                foreach ($related as $rid) {
                    if ($rid == $id) {
                        continue;
                    }

                    \R::exec(
                        "INSERT IGNORE INTO attribute_value_related
                        (attr_value_id, related_attr_value_id, sort)
                        VALUES (?, ?, 500)",
                        [$id, $rid]
                    );
                }

                $technicIds = $_POST['technic_ids'] ?? [];
                $technicIds = array_values(array_unique(array_filter(array_map('intval', (array)$technicIds))));

                \R::exec("DELETE FROM attribute_value_technic WHERE attr_value_id = ?", [$id]);

                foreach ($technicIds as $tid) {
                    \R::exec(
                        "INSERT IGNORE INTO attribute_value_technic
                        (attr_value_id, technic_id, sort)
                        VALUES (?, ?, 500)",
                        [$id, $tid]
                    );
                }

                $_SESSION['success'] = 'Фильтр добавлен';
                redirect();
            }
        }

        $group = \R::findAll('attribute_group');

        $sizeGroup = \R::findOne('attribute_group', 'url_params = ?', ['size']);
        $sizeValues = $sizeGroup
            ? \R::findAll('attribute_value', 'attr_group_id = ? ORDER BY value', [$sizeGroup->id])
            : [];

        $relatedSizeIds = [];
        $technics = \R::findAll('technics_type', "hide = 'show' ORDER BY name");
        $technicIds = [];

        $this->setMeta('Новый фильтр');

        $this->set(compact(
            'group',
            'sizeValues',
            'relatedSizeIds',
            'technics',
            'technicIds'
        ));
    }

    public function groupEditAction()
    {
        if (!empty($_POST)) {
            $id = $this->getRequestID(false);

            $group = new FiltrsGroup();
            $data = $this->normalizeGroupPost($_POST);

            $group->load($data);

            if (!$group->validate($data) || !$group->checkUnique($id)) {
                $group->getErrors();
                redirect();
            }

            $oldGroup = \R::findOne('attribute_group', 'id = ?', [$id]);
            if (!$oldGroup) {
                throw new \Exception('Группа не найдена', 404);
            }

            $needRegenerateAutoTemplate = ((string)$data['template'] === '0');

            if ($needRegenerateAutoTemplate && !empty($oldGroup['url_params'])) {
                $oldFileName = $this->buildControllerNameFromUrlParams((string)$oldGroup['url_params']);
                $this->removeGeneratedGroupFiles($oldFileName);
            }

            if ($group->update('attribute_group', $id)) {
                \R::exec(
                    "UPDATE attribute_group
                    SET template = ?,
                        page_mode = ?,
                        redirect_to_category = ?,
                        canonical_source = ?
                    WHERE id = ?",
                    [
                        (string)($data['template'] ?? '0'),
                        (string)($data['page_mode'] ?? 'standalone'),
                        (int)($data['redirect_to_category'] ?? 0),
                        (string)($data['canonical_source'] ?? 'none'),
                        (int)$id,
                    ]
                );

                if ($needRegenerateAutoTemplate) {
                    $group->addClassGroup($data);
                }

                \R::exec("DELETE FROM attribute_category WHERE group_id = ?", [$id]);

                $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['category_id'] ?? [])))));

                if (!empty($categoryIds)) {
                    $sqlPart = '';

                    foreach ($categoryIds as $catId) {
                        $sqlPart .= "({$catId}, {$id}),";
                    }

                    $sqlPart = rtrim($sqlPart, ',');

                    \R::exec("INSERT INTO attribute_category (category_id, group_id) VALUES {$sqlPart}");
                }

                AdminActivityLogger::admin(29, 'attribute_group', (int)$id);

                $_SESSION['success'] = 'Изменения сохранены';
                redirect();
            }
        }

        $id = $this->getRequestID();
        $group = \R::load('attribute_group', $id);
        $category = \R::findAll('category');

        $this->setMeta("Редактирование группы {$group->title}");
        $this->set(compact('group', 'category'));
    }

    public function groupAddAction()
    {
        if (!empty($_POST)) {
            $group = new FiltrsGroup();
            $data = $this->normalizeGroupPost($_POST);

            $group->load($data);

            if (!$group->validate($data) || !$group->checkUnique()) {
                $group->getErrors();
                redirect();
            }

            if ($id = $group->save('attribute_group', false)) {
                if ((string)$data['template'] === '0') {
                    $group->addClassGroup($data);
                }

                $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['category_id'] ?? [])))));

                if (!empty($categoryIds)) {
                    $sqlPart = '';

                    foreach ($categoryIds as $catId) {
                        $sqlPart .= "({$catId}, {$id}),";
                    }

                    $sqlPart = rtrim($sqlPart, ',');

                    \R::exec("INSERT INTO attribute_category (category_id, group_id) VALUES {$sqlPart}");
                }

                AdminActivityLogger::admin(28, 'attribute_group', (int)$id);

                $_SESSION['success'] = 'Группа добавлена';
                redirect();
            }
        }

        $this->setMeta('Новая группа фильтров');
        $category = \R::findAll('category');
        $this->set(compact('category'));
    }

    public function attributeGroupAction()
    {
        $attrs_group = \R::findAll('attribute_group');
        $this->setMeta('Группы фильтров');
        $this->set(compact('attrs_group'));
    }

    public function attributeAction()
    {
        $sql = "
            SELECT
                av.*,
                ag.title AS gname,
                ag.url_params,

                COALESCE(r.related_sizes_count, 0) AS related_sizes_count,
                COALESCE(t.technic_count, 0)       AS technic_count,
                COALESCE(f.faq_count, 0)           AS faq_count,
                COALESCE(pc.product_count, 0)      AS product_count,
                COALESCE(pc.active_product_count, 0) AS active_product_count,

                COALESCE(cf.total_rules_count, 0) AS total_rules_count,
                COALESCE(cf.landing_count, 0)     AS landing_count,
                COALESCE(cf.redirect_count, 0)    AS redirect_count,
                (
                    SELECT c.alias
                    FROM attribute_value_category_canonical avcc
                    INNER JOIN category c ON c.id = avcc.category_id
                    WHERE avcc.attr_value_id = av.id
                      AND avcc.is_active = 1
                      AND avcc.mode = 'landing'
                      AND c.alias <> ''
                    ORDER BY avcc.id ASC
                    LIMIT 1
                ) AS preview_category_alias

            FROM attribute_value av
            JOIN attribute_group ag ON ag.id = av.attr_group_id

            LEFT JOIN (
                SELECT attr_value_id, COUNT(*) AS related_sizes_count
                FROM attribute_value_related
                GROUP BY attr_value_id
            ) r ON r.attr_value_id = av.id

            LEFT JOIN (
                SELECT attr_value_id, COUNT(*) AS technic_count
                FROM attribute_value_technic
                GROUP BY attr_value_id
            ) t ON t.attr_value_id = av.id

            LEFT JOIN (
                SELECT attr_value_id, COUNT(*) AS faq_count
                FROM attribute_value_category_faq
                WHERE hide = 'show'
                AND TRIM(question) <> ''
                AND TRIM(answer) <> ''
                GROUP BY attr_value_id
            ) f ON f.attr_value_id = av.id

            LEFT JOIN (
                SELECT
                    ap.attr_id,
                    COUNT(DISTINCT p.id) AS product_count,
                    COUNT(DISTINCT CASE WHEN p.hide = 'show' THEN p.id END) AS active_product_count
                FROM attribute_product ap
                INNER JOIN product p ON p.id = ap.product_id
                GROUP BY ap.attr_id
            ) pc ON pc.attr_id = av.id

            LEFT JOIN (
                SELECT
                    attr_value_id,
                    COUNT(*) AS total_rules_count,
                    SUM(CASE WHEN mode = 'landing'  AND is_active = 1 THEN 1 ELSE 0 END) AS landing_count,
                    SUM(CASE WHEN mode = 'redirect' AND is_active = 1 THEN 1 ELSE 0 END) AS redirect_count
                FROM attribute_value_category_canonical
                WHERE is_active = 1
                GROUP BY attr_value_id
            ) cf ON cf.attr_value_id = av.id

            ORDER BY av.value
        ";

        $attrs = \R::getAll($sql);
        $this->setMeta('Фильтры');
        $this->set(compact('attrs'));
    }

    public function deleteBaseimgAction()
    {
        $id = $_POST['id'] ?? null;
        $src = $_POST['src'] ?? null;

        if (!$id || !$src) {
            return;
        }

        if (\R::exec("UPDATE attribute_value SET img = '' WHERE id = ? AND img = ?", [$id, $src])) {
            @unlink(WWW . "/images/filtrs/baseimg/$src");
            exit('1');
        }

        return;
    }

    protected function normalizeGroupPost(array $data): array
    {
        $data['url_params'] = trim(mb_strtolower((string)($data['url_params'] ?? ''), 'UTF-8'), '/');
        $data['page_mode'] = trim((string)($data['page_mode'] ?? '')) ?: 'standalone';
        $data['canonical_source'] = trim((string)($data['canonical_source'] ?? '')) ?: 'none';
        $data['redirect_to_category'] = !empty($data['redirect_to_category']) ? '1' : '0';
        $data['template'] = isset($data['template']) ? (string)$data['template'] : '0';

        return $data;
    }

    protected function buildControllerNameFromUrlParams(string $urlParams): string
    {
        $urlParams = trim(mb_strtolower($urlParams, 'UTF-8'), '/');
        $parts = preg_split('#[^a-z0-9]+#i', $urlParams);
        $parts = array_values(array_filter($parts));

        if (empty($parts)) {
            return '';
        }

        $result = '';

        foreach ($parts as $part) {
            $result .= ucfirst($part);
        }

        return $result;
    }

    protected function removeGeneratedGroupFiles(string $fileName): void
    {
        if ($fileName === '') {
            return;
        }

        $controllerPath = APP . "/controllers/{$fileName}Controller.php";

        if (file_exists($controllerPath)) {
            @chmod($controllerPath, 0666);
            @unlink($controllerPath);
        }

        $dir = APP . "/views/" . TEMPLATE . "/{$fileName}";

        if (file_exists($dir) && is_dir($dir)) {
            chmod($dir, 0777);

            if ($elements = glob($dir . "/*")) {
                foreach ($elements as $element) {
                    if (is_dir($element)) {
                        removeDirectory($element);
                    } else {
                        @chmod($element, 0666);
                        @unlink($element);
                    }
                }
            }

            @rmdir($dir);
        }

        $dirRoute = CONF . '/routes.php';
        $fileSource = file_get_contents($dirRoute);

        if ($fileSource !== false) {
            $fileSource = preg_replace(
                "#\n//" . preg_quote($fileName, '#') . "//.*?//And" . preg_quote($fileName, '#') . "//#is",
                '',
                $fileSource
            );

            @file_put_contents($dirRoute, $fileSource);
        }
    }

    protected function ensureCategorySeoSchema(): void
    {
        $columns = $this->getCategorySeoColumns();
        $alterSql = [];

        if (!isset($columns['canonical_url'])) {
            $alterSql[] = "ADD COLUMN canonical_url VARCHAR(500) NULL AFTER img";
        }

        if (!isset($columns['robots'])) {
            $alterSql[] = "ADD COLUMN robots VARCHAR(100) NULL AFTER canonical_url";
        }

        if (!isset($columns['created_at'])) {
            $alterSql[] = "ADD COLUMN created_at DATETIME NULL AFTER robots";
        }

        if (!isset($columns['updated_at'])) {
            $alterSql[] = "ADD COLUMN updated_at DATETIME NULL AFTER created_at";
        }

        if (empty($alterSql)) {
            return;
        }

        try {
            \R::exec("ALTER TABLE attribute_value_category_canonical " . implode(', ', $alterSql));
        } catch (\Throwable $e) {
            // Если на хостинге нет прав ALTER, админка продолжит работать без optional-полей.
        }
    }

    protected function getCategorySeoColumns(): array
    {
        try {
            $rows = \R::getAll("SHOW COLUMNS FROM attribute_value_category_canonical");
        } catch (\Throwable $e) {
            return [];
        }

        $columns = [];
        foreach ($rows as $row) {
            if (!empty($row['Field'])) {
                $columns[(string)$row['Field']] = true;
            }
        }

        return $columns;
    }
}
