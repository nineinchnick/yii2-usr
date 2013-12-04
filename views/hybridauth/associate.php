<?php /*
@var $this HybridauthController */

$title = Yii::t('UsrModule.usr', 'Log in');
if (isset($this->breadcrumbs))
	$this->breadcrumbs=array($this->module->id, $title);
$this->pageTitle = Yii::app()->name.' - '.$title;
?>
<h1><?php echo $title; ?></h1>

<?php $this->displayFlashes(); ?>

<?php if ($this->module->registrationEnabled): ?>

<div class="<?php echo $this->module->formCssClass; ?>">
<?php $form=$this->beginWidget($this->module->formClass, array(
	'id'=>'localProfile-form',
	'action'=>array($this->action->id),
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	),
	'focus'=>array($localProfile,'username'),
)); ?>

	<?php echo $form->hiddenField($remoteLogin,'provider'); ?>
	<?php echo $form->hiddenField($remoteLogin,'openid_identifier'); ?>

	<div>
		<h3><?php echo Yii::t('UsrModule.usr', 'Create a new account'); ?></h3>

		<p class="note"><?php echo Yii::t('UsrModule.usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

		<?php echo $form->errorSummary($localProfile); ?>

		<div class="control-group">
			<?php echo $form->labelEx($localProfile,'username'); ?>
			<?php echo $form->textField($localProfile,'username'); ?>
			<?php echo $form->error($localProfile,'username'); ?>
		</div>

		<div class="control-group">
			<?php echo $form->labelEx($localProfile,'email'); ?>
			<?php echo $form->textField($localProfile,'email'); ?>
			<?php echo $form->error($localProfile,'email'); ?>
		</div>

		<div class="control-group">
			<?php echo $form->labelEx($localProfile,'firstName'); ?>
			<?php echo $form->textField($localProfile,'firstName'); ?>
			<?php echo $form->error($localProfile,'firstName'); ?>
		</div>

		<div class="control-group">
			<?php echo $form->labelEx($localProfile,'lastName'); ?>
			<?php echo $form->textField($localProfile,'lastName'); ?>
			<?php echo $form->error($localProfile,'lastName'); ?>
		</div>

		<div class="buttons">
			<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Submit'), array('class'=>$this->module->submitButtonCssClass)); ?>
		</div>
	</div>

<?php $this->endWidget(); ?>
</div><!-- form -->

<?php endif; ?>

<div class="<?php echo $this->module->formCssClass; ?>">
<?php $form=$this->beginWidget($this->module->formClass, array(
	'id'=>'localLogin-form',
	'action'=>array($this->action->id),
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	),
	'focus'=>array($localLogin,'username'),
)); ?>

	<?php echo $form->hiddenField($remoteLogin,'provider'); ?>
	<?php echo $form->hiddenField($remoteLogin,'openid_identifier'); ?>

	<div>
		<h3><?php echo Yii::t('UsrModule.usr', 'Log in into existing account'); ?></h3>

		<p class="note"><?php echo Yii::t('UsrModule.usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

		<?php echo $form->errorSummary($localLogin); ?>

<?php if ($localLogin->scenario != 'reset'): ?>
		<div class="control-group">
			<?php echo $form->labelEx($localLogin,'username'); ?>
			<?php echo $form->textField($localLogin,'username'); ?>
			<?php echo $form->error($localLogin,'username'); ?>
		</div>

		<div class="control-group">
			<?php echo $form->labelEx($localLogin,'password'); ?>
			<?php echo $form->passwordField($localLogin,'password'); ?>
			<?php echo $form->error($localLogin,'password'); ?>
		</div>

		<div class="buttons">
			<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Log in'), array('class'=>$this->module->submitButtonCssClass)); ?>
		</div>
<?php else: ?>
		<?php echo $form->hiddenField($localLogin,'username'); ?>
		<?php echo $form->hiddenField($localLogin,'password'); ?>
		<?php echo $form->hiddenField($localLogin,'rememberMe'); ?>

<?php $this->renderPartial('_newpassword', array('form'=>$form, 'model'=>$localLogin)); ?>

		<div class="buttons">
			<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Change password'), array('class'=>$this->module->submitButtonCssClass)); ?>
		</div>
<?php endif; ?>
	</div>
<?php $this->endWidget(); ?>
</div><!-- form -->

