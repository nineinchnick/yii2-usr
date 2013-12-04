	<div class="control-group">
		<?php echo $form->labelEx($model,'newPassword'); ?>
		<?php echo $form->passwordField($model,'newPassword', array('autocomplete'=>'off')); ?>
		<?php echo $form->error($model,'newPassword'); ?>
<?php if ($this->module->dicewareEnabled): ?>
		<p><a id="Users_generatePassword" href="#"><?php echo Yii::t('UsrModule.usr', 'Generate a password'); ?></a></p>
<?php
$diceUrl = $this->createUrl('password');
$diceLabel = Yii::t('UsrModule.usr', 'Use this password?\nTo copy it to the clipboard press Ctrl+C.');
$passwordId = CHtml::activeId($model, 'newPassword');
$verifyId = CHtml::activeId($model, 'newVerify');
$script = <<<JavaScript
$('#Users_generatePassword').on('click',function(){
	$.getJSON('{$diceUrl}', function(data){
		var text = window.prompt("{$diceLabel}", data);
		if (text != null)
			$('#{$passwordId}').val(text);
			$('#{$verifyId}').val(text);
	});
	return false;
});
JavaScript;
Yii::app()->getClientScript()->registerScript(__CLASS__.'#diceware', $script);
?>
<?php endif; ?>
	</div>

	<div class="control-group">
		<?php echo $form->labelEx($model,'newVerify'); ?>
		<?php echo $form->passwordField($model,'newVerify', array('autocomplete'=>'off')); ?>
		<?php echo $form->error($model,'newVerify'); ?>
	</div>
