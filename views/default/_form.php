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

<?= isset($attributesMap['username']) ? $form->field($model, 'username', ['inputOptions' => ['autofocus' => true, 'class' => 'form-control']]) : '' ?>
<?= isset($attributesMap['email']) ? $form->field($model, 'email') : '' ?>
<?= $model->scenario !== 'register' ? $form->field($model, 'password')->passwordInput() : '' ?>
<?= $this->render('_newpassword', ['form' => $form, 'model' => $passwordForm, 'focus' => false]) ?>
<?= isset($attributesMap['firstName']) ? $form->field($model, 'firstName') : '' ?>
<?= isset($attributesMap['lastName']) ? $form->field($model, 'lastName') : '' ?>

<?php if ($identity instanceof PictureIdentityInterface && !empty($model->pictureUploadRules)): ?>
    <?php $picture = $identity->getPictureUrl(80, 80);
    if ($picture !== false) {
        $picture['alt'] = Yii::t('usr', 'Profile picture');
        $url = $picture['url'];
        unset($picture['url']);
    } ?>
    <?= $picture === false ? '' : Html::img($url, $picture); ?>
    <?= $form->field($model, 'picture')->fileInput() ?>
    <?= $form->field($model, 'removePicture')->checkbox() ?>
<?php endif; ?>
