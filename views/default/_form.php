<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use \nineinchnick\usr\components\PictureIdentityInterface;
use \nineinchnick\usr\components\EditableIdentityInterface;

/**
 * @var yii\web\View $this
 * @var models\ProfileForm $model
 * @var models\PasswordForm $passwordForm
 * @var ActiveForm $form
 */

$identity = $model->getIdentity();
$attributesMap = [];
if ($identity instanceof EditableIdentityInterface) {
    $attributesMap = $identity->identityAttributesMap();
}
?>

<?php if (isset($attributesMap['username'])): ?>
    <?= $form->field($model, 'username', ['inputOptions' => ['autofocus' => true, 'class' => 'form-control']]) ?>
<?php endif; ?>

<?php if (isset($attributesMap['email'])): ?>
    <?= $form->field($model, 'email') ?>
<?php endif; ?>

<?php if ($model->scenario !== 'register'): ?>
    <?= $form->field($model, 'password')->passwordInput() ?>
<?php endif; ?>

<?= $this->render('_newpassword', ['form' => $form, 'model' => $passwordForm, 'focus' => false]) ?>

<?php if (isset($attributesMap['firstName'])): ?>
    <?= $form->field($model, 'firstName') ?>
<?php endif; ?>

<?php if (isset($attributesMap['lastName'])): ?>
    <?= $form->field($model, 'lastName') ?>
<?php endif; ?>

<?php if ($model->getIdentity() instanceof PictureIdentityInterface && !empty($model->pictureUploadRules)): ?>
    <?php $picture = $model->getIdentity()->getPictureUrl(80, 80);
    if ($picture !== false) {
        $picture['alt'] = Yii::t('usr', 'Profile picture');
        $url = $picture['url'];
        unset($picture['url']);
    } ?>
    <?= $picture === false ? '' : Html::img($url, $picture); ?>
    <?= $form->field($model, 'picture')->fileInput() ?>
    <?= $form->field($model, 'removePicture')->checkbox() ?>
<?php endif; ?>
