<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\RecoveryForm $model
 * @var ActiveForm $form
 * @var nineinchnick\usr\Module $module
 */
$this->title = Yii::t('usr', 'Username or password recovery');
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<div class="<?= $module->formCssClass; ?>">
<?php $form = ActiveForm::begin([
	'id' => 'recovery-form',
	'enableClientValidation'=>true,
	'validateOnSubmit'=>true,
	//'focus'=>array($model,$model->scenario==='reset' ? 'newPassword' : 'username'),
]); ?>

	<p class="note"><?= Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.') ?></p>

	<?= $form->errorSummary($model) ?>

	<div class="row">
		<div class="col-lg-5">

<?php if ($model->scenario === 'reset'): ?>
			<?= Html::activeHiddenInput($model,'username') ?>
			<?= Html::activeHiddenInput($model,'email') ?>
			<?= Html::activeHiddenInput($model,'activationKey') ?>

<?= $this->render('_newpassword', array('form'=>$form, 'model'=>$model, 'module'=>$module)); ?>
<?php else: ?>
			<?= $form->field($model, 'username') ?>
			<?= $form->field($model, 'email') ?>

<?php if($model->getBehavior('captcha') !== null): ?>
<?= $this->render('_captcha', array('form'=>$form, 'model'=>$model, 'module'=>$module)) ?>
<?php endif; ?>
<?php endif; ?>
			<div class="form-group">
				<?= Html::submitButton(Yii::t('usr', 'Submit'), ['class' => 'btn btn-primary']) ?>
			</div>
		</div>
	</div>

<?php ActiveForm::end(); ?>
</div><!-- form -->
