<?php /*
@var $this DefaultController
@var $model ProfileForm */

$title = Yii::t('UsrModule.usr', 'User profile');
if (isset($this->breadcrumbs))
	$this->breadcrumbs=array($this->module->id, $title);
$this->pageTitle = Yii::app()->name.' - '.$title;
?>
<h1><?php echo $title; ?><small style="margin-left: 1em;"><?php echo CHtml::link(Yii::t('UsrModule.usr', 'update'), array('profile', 'update'=>true)); ?></small></h1>

<?php $this->displayFlashes(); ?>

<?php
$attributes = array('username', 'email', 'firstName', 'lastName');
if ($this->module->oneTimePasswordMode === UsrModule::OTP_TIME || $this->module->oneTimePasswordMode === UsrModule::OTP_COUNTER) {
	$attributes[] = array(
		'name'=>'twoStepAuth',
		'type'=>'raw',
		'label'=>Yii::t('UsrModule.usr', 'Two step authentication'),
		'value'=>$this->displayOneTimePasswordSecret(),
	);
}
$this->widget($this->module->detailViewClass, array('data' => $model, 'attributes' => $attributes));
?>

