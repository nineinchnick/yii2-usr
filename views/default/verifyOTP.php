<?php /*
@var $this DefaultController
@var $model LoginForm */

$title = Yii::t('UsrModule.usr', 'Two step authentication');
if (isset($this->breadcrumbs))
	$this->breadcrumbs=array($this->module->id, $title);
$this->pageTitle = Yii::app()->name.' - '.$title;
?>
<h1><?php echo $title; ?></h1>

<?php $this->displayFlashes(); ?>

<div class="<?php echo $this->module->formCssClass; ?>">
<?php $form=$this->beginWidget($this->module->formClass, array(
	'id'=>'login-form',
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	),
	'focus'=>array($model,'oneTimePassword'),
	'action'=>array('login', 'scenario'=>'verifyOTP'),
)); ?>

	<p class="note"><?php echo Yii::t('UsrModule.usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<p>
<?php if ($this->module->oneTimePasswordMode === UsrModule::OTP_TIME): ?>
		<?php echo Yii::t('UsrModule.usr', 'Use the Google Authenticator application to generate a one time password and enter it below.'); ?><br/>
<?php elseif ($this->module->oneTimePasswordMode === UsrModule::OTP_COUNTER): ?>
		<?php echo Yii::t('UsrModule.usr', 'A one time password has been sent to your email. Enter it below.'); ?><br/>
<?php endif; ?>
	</p>

	<?php echo $form->hiddenField($model,'username'); ?>
	<?php echo $form->hiddenField($model,'password'); ?>
	<?php echo $form->hiddenField($model,'rememberMe'); ?>

	<div class="control-group">
		<?php echo $form->labelEx($model,'oneTimePassword'); ?>
		<?php echo $form->textField($model,'oneTimePassword'); ?>
		<?php echo $form->error($model,'oneTimePassword'); ?>
	</div>

	<div class="buttons">
		<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Submit'), array('class'=>$this->module->submitButtonCssClass)); ?>
	</div>

<?php $this->endWidget(); ?>
</div><!-- form -->
