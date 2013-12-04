<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use nineinchnick\yii2-usr;

/**
 * @var yii\web\View $this
 * @var models\LoginForm $model
 * @var ActiveForm $form
 */
$this->title = Yii::t('UsrModule.usr', 'Log in');
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php $this->displayFlashes(); ?>

<div class="login">

	<p class="note"><?= Yii::t('UsrModule.usr', 'Fields marked with <span class="required">*</span> are required.') ?></p>

	<div class="row">
		<div class="col-lg-5">
			<?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
				<?= $form->field($model, 'username') ?>
				<?= $form->field($model, 'password')->passwordInput() ?>
				<?= $form->field($model, 'rememberMe')->checkbox() ?>
				<div style="color:#999;margin:1em 0">
					If you forgot your password you can <?= Html::a('reset it', ['site/request-password-reset']) ?>.
				</div>
<?php if ($this->module->recoveryEnabled): ?>
	<p>
		<?= Yii::t('UsrModule.usr', 'Don\'t remember username or password?') ?>
		<?= Yii::t('UsrModule.usr', 'Go to {link}.', array('{link}'=>Html::a(Yii::t('UsrModule.usr', 'password recovery'), ['recovery']))) ?>
	</p>
<?php endif; ?>
<?php if ($this->module->registrationEnabled): ?>
	<p>
		<?= Yii::t('UsrModule.usr', 'Don\'t have an account yet?') ?>
		<?= Yii::t('UsrModule.usr', 'Go to {link}.', array( '{link}'=>Html::a(Yii::t('UsrModule.usr', 'registration'), ['register']))) ?>
	</p>
<?php endif; ?>
<?php if ($this->module->hybridauthEnabled()): ?>
	<p>
		<ul>
<?php Yii::app()->clientScript->registerCssFile(Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias($this->module->id.'.components.assets.zocial')).'/zocial.css'); ?>
<?php foreach ($this->module->hybridauthProviders as $provider => $settings): if(!$settings['enabled']) continue; ?>
			<li>
				<a class="zocial <?= strtolower($provider) ?>" href="<?= $this->createUrl('hybridauth/login', ['provider'=>$provider]) ?>">
					<?= Yii::t('UsrModule.usr', 'Log in using {provider}', ['{provider}'=>$provider]); ?>
				</a>
			</li>
<?php endforeach; ?>
		</ul>
	</p>
<?php endif; ?>
				<div class="form-group">
					<?= Html::submitButton(Yii::t('UsrModule.usr', 'Log in'), ['class' => 'btn btn-primary']) ?>
				</div>
			<?php ActiveForm::end(); ?>
		</div>
	</div>

	<?php echo $form->errorSummary($model); ?>

</div><!-- form -->
