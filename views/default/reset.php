<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\LoginForm $model
 * @var ActiveForm $form
 */
$this->title = Yii::t('usr', 'Password reset');
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<div class="<?php echo $this->context->module->formCssClass; ?>">
<?php $form = ActiveForm::begin([
    'id' => 'login-form',
    'enableClientValidation' => true,
    'validateOnSubmit' => true,
    'action' => ['login', 'scenario' => 'reset'],
]); ?>

    <p class="note"><?php echo Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

    <?php echo $form->errorSummary($model); ?>

    <?= Html::activeHiddenInput($model, 'username') ?>
    <?= Html::activeHiddenInput($model, 'password') ?>
    <?= Html::activeHiddenInput($model, 'rememberMe') ?>

    <div class="row">
        <div class="col-lg-5">

<?= $this->render('_newpassword', ['form' => $form, 'model' => $model, 'focus' => true]); ?>

            <div class="form-group">
                <?= Html::submitButton(Yii::t('usr', 'Change password'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>
</div><!-- form -->
