<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model nineinchnick\usr\models\SearchForm */
/* @var $form yii\widgets\ActiveForm */

$booleanData = [
    Yii::t('manager', 'No'),
    Yii::t('manager', 'Yes'),
];
$booleanOptions = [
    'empty' => Yii::t('manager', 'Any'),
    'separator' => '',
];
$booleanLabel = [
    'style' => 'display: inline; float: none;',
];
?>

<div class="wide form">

    <?php $form = ActiveForm::begin([
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>
    <?= $form->field($model, 'username') ?>
    <?= $form->field($model, 'email') ?>
    <?= $form->field($model, 'firstName') ?>
    <?= $form->field($model, 'lastName') ?>
    <?= $form->field($model, 'createdOn') ?>
    <?= $form->field($model, 'updatedOn') ?>
    <?= $form->field($model, 'lastVisitOn') ?>
    <?= $form->field($model, 'emailVerified')->checkboxList($booleanData, $booleanOptions)->label(null, $booleanLabel) ?>
    <?= $form->field($model, 'isActive')->checkboxList($booleanData, $booleanOptions)->label(null, $booleanLabel) ?>
    <?= $form->field($model, 'isDisabled')->checkboxList($booleanData, $booleanOptions)->label(null, $booleanLabel) ?>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton(Yii::t('manager', 'Search'), ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div><!-- search-form -->
