<?php foreach ($this->groups as $group_id => $group_title): ?>
    <section class="sky-form col-md-3">
        <div class="row1">
            <div class="col">
                <?php if (!empty($this->attrs[$group_id])): ?>
                    <select
                        class="form-control js-filter-select"
                        data-placeholder="<?= h($group_title) ?>"
                        data-category-alias="<?= h($this->categoryAlias ?? '') ?>"
                    >
                        <option value=""><?= h($group_title) ?></option>

                        <?php foreach ($this->attrs[$group_id] as $attr_id => $item): ?>
                            <option
                                value="<?= (int)$attr_id; ?>"
                                data-alias="<?= h($item['alias'] ?? ''); ?>"
                                <?= (!empty($this->filter) && in_array((int)$attr_id, array_map('intval', $this->filter), true)) ? 'selected' : ''; ?>
                            >
                                <?= h($item['value'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endforeach; ?>