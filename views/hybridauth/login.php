<?php /*
@var $this HybridauthController */

$title = Yii::t('UsrModule.usr', 'Log in using {provider}', array('{provider}'=>$remoteLogin->provider));
if (isset($this->breadcrumbs))
	$this->breadcrumbs=array($this->module->id, $title);
$this->pageTitle = Yii::app()->name.' - '.$title;

?>
<h1><?php echo CHtml::encode($title); ?></h1>

<?php $this->displayFlashes(); ?>

<div class="<?php echo $this->module->formCssClass; ?>">
<?php $form=$this->beginWidget($this->module->formClass, array(
	'id'=>'remoteLogin-form',
	'action'=>array($this->action->id),
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	),
	'focus'=>$remoteLogin->requiresFilling() ? array($remoteLogin,'openid_identifier') : null,
)); ?>

	<?php echo $form->hiddenField($remoteLogin,'provider'); ?>

	<div style="<?php echo $remoteLogin->requiresFilling() ? '' : 'display: none;'; ?>">
		<p class="note"><?php echo Yii::t('UsrModule.usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

		<?php echo $form->errorSummary($remoteLogin); ?>

		<div class="control-group">
			<?php echo $form->labelEx($remoteLogin,'openid_identifier'); ?>
			<?php echo $form->textField($remoteLogin,'openid_identifier'); ?>
			<?php echo $form->error($remoteLogin,'openid_identifier'); ?>
		</div>

		<div class="buttons">
			<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Log in'), array('class'=>$this->module->submitButtonCssClass)); ?>
		</div>
	</div>

	</div>
<?php $this->endWidget(); ?>
</div><!-- form -->

