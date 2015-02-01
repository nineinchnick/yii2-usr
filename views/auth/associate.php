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

<?php if ($this->context->module->registrationEnabled): ?>

<div class="<?= $this->context->module->formCssClass; ?>">
<?php $form = ActiveForm::begin([
    'id' => 'localProfile-form',
    'enableClientValidation' => true,
    'validateOnSubmit' => true,
]); ?>

    <?= Html::activeHiddenInput($remoteLogin, 'provider') ?>
    <?= Html::activeHiddenInput($remoteLogin, 'openid_identifier') ?>

    <h3><?= Yii::t('usr', 'Create a new account') ?></h3>

    <p class="note"><?= Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.') ?></p>

    <?= $form->errorSummary($localProfile) ?>

    <div class="row">
        <div class="col-lg-5">

            <?= $form->field($localProfile, 'username', ['inputOptions' => ['autofocus' => true, 'class' => 'form-control']]) ?>
            <?= $form->field($localProfile, 'email') ?>
            <?= $form->field($localProfile, 'firstName') ?>
            <?= $form->field($localProfile, 'lastName') ?>

<?php if ($localProfile->getBehavior('captcha') !== null): ?>
<?= $this->render('/default/_captcha', ['form' => $form, 'model' => $localProfile]) ?>
<?php endif; ?>

            <div class="form-group">
                <?= Html::submitButton(Yii::t('usr', 'Submit'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>
</div><!-- form -->

<?php endif; ?>

<div class="<?= $this->context->module->formCssClass; ?>">
<?php $form = ActiveForm::begin([
    'id' => 'localLogin-form',
    'enableClientValidation' => true,
    'validateOnSubmit' => true,
]); ?>

    <?= Html::activeHiddenInput($remoteLogin, 'provider') ?>
    <?= Html::activeHiddenInput($remoteLogin, 'openid_identifier') ?>

    <h3><?= Yii::t('usr', 'Log in into existing account') ?></h3>

    <p class="note"><?= Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.') ?></p>

    <?php echo $form->errorSummary($localLogin); ?>

    <div class="row">
        <div class="col-lg-5">

<?php if ($localLogin->scenario != 'reset'): ?>
            <?= $form->field($localLogin, 'username', ['inputOptions' => ['autofocus' => true, 'class' => 'form-control']]) ?>
            <?= $form->field($localLogin, 'password')->passwordInput() ?>

            <div class="form-group">
                <?= Html::submitButton(Yii::t('usr', 'Log in'), ['class' => 'btn btn-primary']) ?>
            </div>
<?php else: ?>
    <?= Html::activeHiddenInput($localLogin, 'username') ?>
    <?= Html::activeHiddenInput($localLogin, 'password') ?>
    <?= Html::activeHiddenInput($localLogin, 'rememberMe') ?>

<?= $this->render('_newpassword', ['form' => $form, 'model' => $localLogin, 'focus' => true]); ?>

            <div class="form-group">
                <?= Html::submitButton(Yii::t('usr', 'Change password'), ['class' => 'btn btn-primary']) ?>
            </div>
<?php endif; ?>
        </div>
    </div>
<?php ActiveForm::end(); ?>
</div><!-- form -->
