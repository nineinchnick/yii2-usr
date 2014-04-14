<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\SearchForm $model
 * @var ActiveForm $form
 */

$booleanData = ['' => Yii::t('manager', 'Any'), 0 => Yii::t('manager', 'No'), 1 => Yii::t('manager', 'Yes')];
$booleanOptions = ['labelOptions' => ['style'=>'display: inline; float: none;']];
?>

<div class="wide form">

<?php $form = ActiveForm::begin([
    'action'=>yii\helpers\Url::toRoute(Yii::$app->requestedRoute),
    'method'=>'get',
]); ?>

    <?= $form->field($model, 'id') ?>
    <?= $form->field($model, 'username') ?>
    <?= $form->field($model, 'email') ?>
    <?= $form->field($model, 'firstName') ?>
    <?= $form->field($model, 'lastName') ?>
    <?= $form->field($model, 'createdOn') ?>
    <?= $form->field($model, 'updatedOn') ?>
    <?= $form->field($model, 'lastVisitOn') ?>
    <?= $form->field($model, 'emailVerified', $booleanOptions)->radioList($booleanData) ?>
    <?= $form->field($model, 'isActive', $booleanOptions)->radioList($booleanData) ?>
    <?= $form->field($model, 'isDisabled', $booleanOptions)->radioList($booleanData) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('manager', 'Search'), ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end(); ?>

</div><!-- search-form -->
