<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var models\ProfileForm $model
 * @var ActiveForm $form
 * @var nineinchnick\usr\Module $module
 */
$this->title = Yii::t('usr', 'User profile');
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?><small style="margin-left: 1em;"><?= Html::a(Yii::t('usr', 'update'), ['profile', 'update'=>true]); ?></small></h1>

<?= components\Alerts::widget() ?>

<?php
$attributes = ['username', 'email', 'firstName', 'lastName'];
if ($module->oneTimePasswordMode === nineinchnick\usr\Module::OTP_TIME || $module->oneTimePasswordMode === nineinchnick\usr\Module::OTP_COUNTER) {
	$attributes[] = [
		'name'=>'twoStepAuth',
		'type'=>'raw',
		'label'=>Yii::t('usr', 'Two step authentication'),
		'value'=>Yii::$app->controller->displayOneTimePasswordSecret(),
	];
}
echo DetailView::widget(['model' => $model, 'attributes' => $attributes]);

