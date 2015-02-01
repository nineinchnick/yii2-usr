<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\ProfileForm $model
 * @var models\PasswordForm $passwordForm
 * @var ActiveForm $form
 */
?>

            <?= $form->field($model, 'username', ['inputOptions' => ['autofocus' => true, 'class' => 'form-control']]) ?>
            <?= $form->field($model, 'email') ?>

<?php if ($model->scenario !== 'register'): ?>
            <?= $form->field($model, 'password')->passwordInput() ?>
<?php endif; ?>

<?= $this->render('_newpassword', ['form' => $form, 'model' => $passwordForm, 'focus' => false]) ?>

            <?= $form->field($model, 'firstName') ?>
            <?= $form->field($model, 'lastName') ?>
<?php if ($model->getIdentity() instanceof \nineinchnick\usr\components\PictureIdentityInterface && !empty($model->pictureUploadRules)):
        $picture = $model->getIdentity()->getPictureUrl(80, 80);
        if ($picture !== false) {
            $picture['alt'] = Yii::t('usr', 'Profile picture');
            $url = $picture['url'];
            unset($picture['url']);
        }
?>
            <?= $picture === false ? '' : Html::img($url, $picture); ?>
            <?= $form->field($model, 'picture')->fileInput() ?>
            <?= $form->field($model, 'removePicture')->checkbox() ?>
<?php endif; ?>
