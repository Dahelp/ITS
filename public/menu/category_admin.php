<?php
$parent = isset($category['childs']);
if(!$parent){
    $delete = '<a href="' . ADMIN . '/category/delete?id=' . $id . '" class="delete"><i class="fas fa-times-circle text-danger"></i></a>';
}else{
    $delete = '';
}
?>
<tr class="cont_td_znach">
    <td><?=$id;?></td>
	<td style="text-align:center">
		<?php if(!empty($category['img'])) { ?>
			<img src="/images/category/baseimg/<?=$category['img'];?>" alt="" style="max-height: 50px;">
		<?php }else{ ?>
			<img src="/images/nof.jpg" alt="" style="max-height: 50px;">
		<?php } ?>
		</td>
    <td><?=$category['name'];?></td>
	<td>[../category/<?=$category['alias'];?>]</td>
	<td><?=$category['position'];?></td>
    <td style="text-align:center">
		<?php 
			if(!empty($category['title'])) { $s1 = 20; }
			if(!empty($category['description'])) { $s2 = 20; }
			if(!empty($category['keywords'])) { $s3 = 20; }
			if(!empty($category['content'])) { $s4 = 20; }
			if(!empty($category['img'])) { $s5 = 20; }
			$seo = $s1+$s2+$s3+$s4+$s5; 
		?>
		<?php if($seo == 20) { ?><span class="badge bg-danger">20%</span><?php } ?>
		<?php if($seo == 40) { ?><span class="badge bg-danger">40%</span><?php } ?>
		<?php if($seo == 60) { ?><span class="badge bg-warning">60%</span><?php } ?>
		<?php if($seo == 80) { ?><span class="badge bg-warning">80%</span><?php } ?>
		<?php if($seo == 100) { ?><span class="badge bg-success">100%</span><?php } ?>
	</td>
	<td><a href="<?=ADMIN;?>/category/edit?id=<?=$id;?>"><i class="fas fa-pencil-alt"></i></a> <?=$delete;?> <a target="_blank" href="/category/<?=$category['alias'];?>"><i class="fas fa-eye"></i></a></td>
</tr>
<?php if($parent): ?>
    <div class="list-group">
        <?= $this->getMenuHtml($category['childs']); ?>
    </div>
<?php endif; ?>
