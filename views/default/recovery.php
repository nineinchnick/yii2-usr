<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\RecoveryForm $model
 * @var ActiveForm $form
 */
$this->title = Yii::t('usr', 'Username or password recovery');
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<div class="<?= $this->context->module->formCssClass; ?>">
<?php $form = ActiveForm::begin([
    'id' => 'recovery-form',
    'enableClientValidation' => true,
    'validateOnSubmit' => $model->getBehavior('captcha') === null,
]); ?>

    <p class="note"><?= Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.') ?></p>

    <?= $form->errorSummary($model) ?>

    <div class="row">
        <div class="col-lg-5">

<?php if ($model->scenario === 'reset'): ?>
            <?= Html::activeHiddenInput($model, 'username') ?>
            <?= Html::activeHiddenInput($model, 'email') ?>
            <?= Html::activeHiddenInput($model, 'activationKey') ?>

<?= $this->render('_newpassword', ['form' => $form, 'model' => $model, 'focus' => true]); ?>

<?php else: ?>
            <?= $form->field($model, 'username', ['inputOptions' => ['autofocus' => true, 'class' => 'form-control']]) ?>
            <?= $form->field($model, 'email') ?>

<?php if ($model->getBehavior('captcha') !== null): ?>
<?= $this->render('_captcha', ['form' => $form, 'model' => $model]) ?>
<?php endif; ?>

<?php endif; // $model->scenario == 'reset' ?>
            <div class="form-group">
                <?= Html::submitButton(Yii::t('usr', 'Submit'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>
</div><!-- form -->
