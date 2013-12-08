<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\LoginForm $model
 * @var ActiveForm $form
 */
$this->title = Yii::t('usr', 'Log in');
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<div class="<?= $this->context->module->formCssClass; ?>">
<?php $form = ActiveForm::begin([
    'id' => 'login-form',
	'enableClientValidation'=>true,
	'validateOnSubmit'=>true,
]); ?>

	<p class="note"><?= Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.') ?></p>

	<?= $form->errorSummary($model) ?>

	<div class="row">
		<div class="col-lg-5">
			<?= $form->field($model, 'username', ['inputOptions'=>['autofocus'=>true, 'class' => 'form-control']]) ?>
			<?= $form->field($model, 'password')->passwordInput() ?>
			<?= $form->field($model, 'rememberMe')->checkbox() ?>
<?php if ($this->context->module->recoveryEnabled): ?>
			<p style="color:#999;margin:1em 0">
				<?= Yii::t('usr', 'Don\'t remember username or password?') ?>
				<?= Yii::t('usr', 'Go to {link}.', ['link'=>Html::a(Yii::t('usr', 'password recovery'), ['recovery'])]) ?>
			</p>
<?php endif; ?>
<?php if ($this->context->module->registrationEnabled): ?>
			<p style="color:#999;margin:1em 0">
				<?= Yii::t('usr', 'Don\'t have an account yet?') ?>
				<?= Yii::t('usr', 'Go to {link}.', ['link'=>Html::a(Yii::t('usr', 'registration'), ['register'])]) ?>
			</p>
<?php endif; ?>
<?php if ($this->context->module->hybridauthEnabled()): ?>
			<p>
				<ul>
<?php Yii::app()->clientScript->registerCssFile(Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias($this->context->module->id.'.components.assets.zocial')).'/zocial.css'); ?>
<?php foreach ($this->context->module->hybridauthProviders as $provider => $settings): if(!$settings['enabled']) continue; ?>
					<li>
						<a class="zocial <?= strtolower($provider) ?>" href="<?= $this->createUrl('hybridauth/login', ['provider'=>$provider]) ?>">
							<?= Yii::t('usr', 'Log in using {provider}', ['provider'=>$provider]); ?>
						</a>
					</li>
<?php endforeach; ?>
				</ul>
			</p>
<?php endif; ?>
			<div class="form-group">
				<?= Html::submitButton(Yii::t('usr', 'Log in'), ['class' => 'btn btn-primary']) ?>
			</div>
		</div>
	</div>

<?php ActiveForm::end(); ?>
</div><!-- form -->
