<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\LoginForm $model
 * @var ActiveForm $form
 */
$this->title = Yii::t('usr', 'Two step authentication');
$this->params['breadcrumbs'][] = $this->title;

$otp = $model->getBehavior('oneTimePasswordBehavior');
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<div class="<?= $this->context->module->formCssClass; ?>">
<?php $form = ActiveForm::begin([
    'id' => 'login-form',
    'enableClientValidation' => true,
    'validateOnSubmit' => true,
    'action' => ['login', 'scenario' => 'verifyOTP'],
]); ?>

    <p class="note"><?= Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

    <?= $form->errorSummary($model); ?>

    <p>
<?php if ($otp === \nineinchnick\usr\components\OneTimePasswordFormBehavior::OTP_TIME): ?>
        <?php echo Yii::t('usr', 'Use the Google Authenticator application to generate a one time password and enter it below.'); ?><br/>
<?php elseif ($otp === \nineinchnick\usr\components\OneTimePasswordFormBehavior::OTP_COUNTER): ?>
        <?php echo Yii::t('usr', 'A one time password has been sent to your email. Enter it below.'); ?><br/>
<?php endif; ?>
    </p>

    <?= Html::activeHiddenInput($model, 'username') ?>
    <?= Html::activeHiddenInput($model, 'password') ?>
    <?= Html::activeHiddenInput($model, 'rememberMe') ?>

    <div class="row">
        <div class="col-lg-5">

            <?= $form->field($model, 'oneTimePassword', ['inputOptions' => ['autofocus' => true, 'class' => 'form-control']]) ?>

            <div class="form-group">
                <?= Html::submitButton(Yii::t('usr', 'Submit'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>
</div><!-- form -->
