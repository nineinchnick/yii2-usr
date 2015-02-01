<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\AuthForm $remoteLogin
 * @var models\LoginForm $localLogin
 * @var models\ProfileForm $localProfile
 * @var ActiveForm $form
 */
$this->title = Yii::t('usr', 'Log in using {provider}', ['provider' => $remoteLogin->provider]);
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<div class="<?= $this->context->module->formCssClass; ?>">
<?php $form = ActiveForm::begin([
    'id' => 'remoteLogin-form',
    'enableClientValidation' => true,
    'validateOnSubmit' => true,
]); ?>

    <?= Html::activeHiddenInput($remoteLogin, 'provider') ?>

    <div style="<?= $remoteLogin->requiresFilling() ? '' : 'display: none;'; ?>">
        <p class="note"><?= Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.') ?></p>

        <?= $form->errorSummary($remoteLogin); ?>

        <div class="row">
            <div class="col-lg-5">

            <?= $form->field($remoteLogin, 'openid_identifier') ?>

            <div class="form-group">
                <?= Html::submitButton(Yii::t('usr', 'Log in'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>
</div><!-- form -->
