<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\SearchForm $model
 * @var ActiveForm $form
 */

$booleanData = array(Yii::t('manager', 'No'), Yii::t('manager', 'Yes'));
$booleanOptions = array('empty'=>Yii::t('manager', 'Any'), 'separator' => '', 'labelOptions' => array('style'=>'display: inline; float: none;'));
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
    <?= $form->field($model, 'emailVerified')->radioList($booleanData, $booleanOptions) ?>
    <?= $form->field($model, 'isActive')->radioList($booleanData, $booleanOptions) ?>
    <?= $form->field($model, 'isDisabled')->radioList($booleanData, $booleanOptions) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('manager', 'Search'), ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end(); ?>

</div><!-- search-form -->
