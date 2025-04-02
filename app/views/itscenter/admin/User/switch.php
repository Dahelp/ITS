<div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
	<input type="checkbox" data-id="<?=$id;?>" class="custom-control-input switch-newsletter" id="customSwitch3" <?php if($checked == 0) { echo "data-checked=\"1\""; }else{ echo "data-checked=\"0\""; }?> <?php if($checked == 0) { echo "checked"; }?>>
	<label class="custom-control-label" for="customSwitch3">Вкл/Выкл</label>
</div>