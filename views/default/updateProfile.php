<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\ProfileForm $model
 * @var ActiveForm $form
 */
if ($model->scenario == 'register') {
    $this->title = Yii::t('usr', 'Registration');
} else {
    $this->title = Yii::t('usr', 'User profile');
}
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<div class="<?php echo $this->context->module->formCssClass; ?>">

<?php $form = ActiveForm::begin([
    'id' => 'profile-form',
    'enableAjaxValidation' => true,
    'enableClientValidation' => false,
    'validateOnSubmit' => $model->getBehavior('captcha') === null,
    'options' => ['enctype' => 'multipart/form-data'],
]); ?>

    <p class="note"><?= Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.') ?></p>

    <?= $form->errorSummary([$model, $passwordForm]) ?>

    <div class="row">
        <div class="col-lg-5">

<?= $this->render('_form', [
    'form' => $form,
    'model' => $model,
    'passwordForm' => $passwordForm,
]); ?>

<?php if ($model->getBehavior('captcha') !== null): ?>
<?= $this->render('_captcha', ['form' => $form, 'model' => $model]) ?>
<?php endif; ?>

            <div class="form-group">
                <?= Html::submitButton(Yii::t('usr', 'Submit'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>
</div><!-- form -->
