<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var models\ProfileForm $model
 * @var ActiveForm $form
 */
$this->title = Yii::t('usr', 'User profile');
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?><small style="margin-left: 1em;"><?= Html::a(Yii::t('usr', 'update'), ['profile', 'update'=>true]); ?></small></h1>

<?= components\Alerts::widget() ?>

<?php
$attributes = ['username', 'email', 'firstName', 'lastName'];
if ($this->context->module->oneTimePasswordMode === nineinchnick\usr\Module::OTP_TIME || $this->context->module->oneTimePasswordMode === nineinchnick\usr\Module::OTP_COUNTER) {
	$attributes[] = [
		'name'=>'twoStepAuth',
		'format'=>'raw',
		'label'=>Yii::t('usr', 'Two step authentication'),
		'value'=>$model->getIdentity()->getOneTimePasswordSecret() === null ? Html::a(Yii::t('usr', 'Enable'), ['toggleOneTimePassword']) : Html::a(Yii::t('usr', 'Disable'), ['toggleOneTimePassword']),
	];
}
if ($model->getIdentity() instanceof PictureIdentityInterface) {
       $picture = $model->getIdentity()->getPictureUrl(80,80);
	   $picture['alt'] = Yii::t('usr', 'Profile picture');
       $url = $picture['url'];
       unset($picture['url']);
       array_unshift($attributes, [
		   'name'=>'picture',
		   'type'=>'raw',
		   'label'=>Yii::t('usr', 'Profile picture'),
		   'value'=>yii\helpers\Html::img($url, $picture),
       ]);
}

echo DetailView::widget(['model' => $model, 'attributes' => $attributes]);

