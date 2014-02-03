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
    'enableAjaxValidation'=>true,
	'enableClientValidation'=>false,
	'validateOnSubmit'=>$model->getBehavior('captcha') === null,
	'htmlOptions' => array('enctype' => 'multipart/form-data'),
]); ?>

	<p class="note"><?= Yii::t('usr', 'Fields marked with <span class="required">*</span> are required.') ?></p>

	<?= $form->errorSummary([$model, $passwordForm]) ?>

	<div class="row">
		<div class="col-lg-5">
			<?= $form->field($model, 'username', ['inputOptions'=>['autofocus'=>true, 'class'=>'form-control']]) ?>
			<?= $form->field($model, 'email') ?>

<?php if ($model->scenario !== 'register'): ?>
			<?= $form->field($model, 'password')->passwordInput() ?>
<?php endif; ?>

<?= $this->render('_newpassword', ['form'=>$form, 'model'=>$passwordForm, 'focus'=>false]) ?>

			<?= $form->field($model, 'firstName') ?>
			<?= $form->field($model, 'lastName') ?>
<?php if ($model->getIdentity() instanceof PictureIdentityInterface && !empty($model->pictureUploadRules)):
		$picture = $model->getIdentity()->getPictureUrl(80,80);
		$picture['alt'] = Yii::t('usr', 'Profile picture');
		$url = $picture['url'];
		unset($picture['url']);
?>
			<?= Html::img($url, $picture); ?>
			<?= $form->field($model, 'picture')->fileInput() ?>
			<?= $form->field($model, 'removePicture')->checkbox() ?>
<?php endif; ?>


<?php if($model->getBehavior('captcha') !== null): ?>
<?= $this->render('_captcha', ['form'=>$form, 'model'=>$model]) ?>
<?php endif; ?>

			<div class="form-group">
				<?= Html::submitButton(Yii::t('usr', 'Submit'), ['class' => 'btn btn-primary']) ?>
			</div>
		</div>
	</div>

<?php ActiveForm::end(); ?>
</div><!-- form -->
