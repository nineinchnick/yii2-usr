	<div class="control-group">
		<?php echo $form->labelEx($model,'verifyCode'); ?>
		<div>
		<?php $this->widget('CCaptcha', $this->module->captcha === true ? array() : $this->module->captcha); ?><br/>
		<?php echo $form->textField($model,'verifyCode'); ?>
		</div>
		<div class="hint">
			<?php echo Yii::t('UsrModule.usr', 'Please enter the letters as they are shown in the image above.'); ?><br/>
			<?php echo Yii::t('UsrModule.usr', 'Letters are not case-sensitive.'); ?>
		</div>
		<?php echo $form->error($model,'verifyCode'); ?>
	</div>
