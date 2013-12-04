<?php /*
@var $this DefaultController
@var $model LoginForm */

$title = Yii::t('UsrModule.usr', 'Password reset');
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
	'focus'=>array($model,'newPassword'),
	'action'=>array('login', 'scenario'=>'reset'),
)); ?>

	<p class="note"><?php echo Yii::t('UsrModule.usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<?php echo $form->hiddenField($model,'username'); ?>
	<?php echo $form->hiddenField($model,'password'); ?>
	<?php echo $form->hiddenField($model,'rememberMe'); ?>

<?php $this->renderPartial('_newpassword', array('form'=>$form, 'model'=>$model)); ?>

	<div class="buttons">
		<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Change password'), array('class'=>$this->module->submitButtonCssClass)); ?>
	</div>

<?php $this->endWidget(); ?>
</div><!-- form -->
