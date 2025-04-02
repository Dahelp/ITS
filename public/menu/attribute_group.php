<?php $parent_id = \ishop\App::$app->getProperty('parent_id'); ?>
<option value="<?=$id;?>"<?php if($id == $parent_id) echo ' selected'; ?>>
    <?=$tab . $attribute['name'];?>
</option>
<?php if(isset($attribute['childs'])): ?>
    <?= $this->getMenuHtml($attribute['childs'], '&nbsp;' . $tab. '-') ?>
<?php endif; ?>