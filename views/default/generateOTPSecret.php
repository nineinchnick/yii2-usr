<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\OneTimePasswordForm $model
 * @var string $url
 * @var ActiveForm $form
 * @var string $mode
 */
$this->title = Yii::t('usr', 'One Time Password Secret');
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<div class="<?= $this->context->module->formCssClass; ?>">
<?php $form = ActiveForm::begin([
    'id' => 'secret-form',
    'enableClientValidation' => false,
    'validateOnSubmit' => false,
]); ?>

    <?= $form->errorSummary($model) ?>

    <div class="row">
        <div class="col-lg-5">

    <p>
<?php if ($mode === \nineinchnick\usr\components\OneTimePasswordFormBehavior::OTP_TIME): ?>
        <?php echo Yii::t('usr', 'Scan this QR code using the Google Authenticator application in your mobile phone.'); ?><br/>
        <?php echo Html::img($url, ['alt' => Yii::t('usr', 'One Time Password Secret')]); ?><br/>
        <?php echo Yii::t('usr', 'Use the Google Authenticator application to generate a one time password and enter it below.'); ?><br/>
<?php elseif ($mode === \nineinchnick\usr\components\OneTimePasswordFormBehavior::OTP_COUNTER): ?>
        <?php echo Yii::t('usr', 'A one time password has been sent to your email. Enter it below.'); ?><br/>
<?php endif; ?>
    </p>

            <?= $form->field($model, 'oneTimePassword', ['inputOptions' => ['autofocus' => true, 'class' => 'form-control']]) ?>

            <div class="form-group">
                <?= Html::submitButton(Yii::t('usr', 'Submit'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>
</div><!-- form -->
