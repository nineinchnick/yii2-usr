<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var \yii\web\View $this */
/* @var \nineinchnick\usr\models\ProfileForm $profileForm */
/* @var \nineinchnick\usr\models\PasswordForm $passwordForm */
/* @var \nineinchnick\usr\Module $module */
/* @var \app\models\User $identity */
/* @var \yii\rbac\ManagerInterface $authManager */

$module = $this->context->module;
$identity = $profileForm->getIdentity();
$authManager = Yii::$app->getAuthManager();
$assignedRoles = $id === null ? [] : $authManager->getRolesByUser($id);
$allRoles = $authManager->getRoles();

$this->title = $id === null
    ? Yii::t('manager', 'Create user')
    : Yii::t('manager', 'Update user {id}', ['id' => $profileForm->username]);

$this->params['menu'] = [
    ['label' => Yii::t('manager', 'List users'), 'url' => ['index']],
];
if ($id !== null) {
    $this->params['menu'][] = ['label' => Yii::t('manager', 'Create user'), 'url' => ['update']];
}

$detailViewClass = $module->detailViewClass;

?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<div class="<?php echo $module->formCssClass; ?>">

<?php if ($id !== null): ?>
<?= $detailViewClass::widget([
    'model' => $identity,
    'attributes' => [
        [
            'attribute' => 'createdOn',
            'format' => 'datetime',
            'label' => Yii::t('manager', 'Created On'),
            'value' => $identity->getTimestamps("createdOn"),
        ],
        [
            'attribute' => 'updatedOn',
            'format' => 'datetime',
            'label' => Yii::t('manager', 'Updated On'),
            'value' => $identity->getTimestamps("updatedOn"),
        ],
        [
            'attribute' => 'lastVisitOn',
            'format' => 'datetime',
            'label' => Yii::t('manager', 'Last Visit On'),
            'value' => $identity->getTimestamps("lastVisitOn"),
        ],
        [
            'attribute' => 'passwordSetOn',
            'format' => 'datetime',
            'label' => Yii::t('manager', 'Password Set On'),
            'value' => $identity->getTimestamps("passwordSetOn"),
        ],
        [
            'attribute' => 'emailVerified',
            'format' => 'raw',
            'label' => Yii::t('manager', 'Email Verified'),
            'value' => Html::a(
                $identity->isVerified() ? Yii::t("manager", "Yes") : Yii::t("manager", "No"),
                ["verify", "id" => $identity->id],
                [
                    "class" => "actionButton",
                    "title" => Yii::t("manager", "Toggle"),
                ]
            ),
        ],
        [
            'attribute' => 'isActive',
            'format' => 'raw',
            'label' => Yii::t('manager', 'Is Active'),
            'value' => Html::a(
                $identity->isActive() ? Yii::t("manager", "Yes") : Yii::t("manager", "No"),
                ["activate", "id" => $identity->id],
                [
                    "class" => "actionButton",
                    "title" => Yii::t("manager", "Toggle"),
                ]
            ),
        ],
        [
            'attribute' => 'isDisabled',
            'format' => 'raw',
            'label' => Yii::t('manager', 'Is Disabled'),
            'value' => Html::a(
                $identity->isDisabled() ? Yii::t("manager", "Yes") : Yii::t("manager", "No"),
                ["disable", "id" => $identity->id],
                [
                    "class" => "actionButton",
                    "title" => Yii::t("manager", "Toggle"),
                ]
            ),
        ],
    ],
]); ?>
<?php endif; ?>

<?php $form = ActiveForm::begin([
    'id' => 'profile-form',
    'enableAjaxValidation' => true,
    'enableClientValidation' => false,
    'validateOnSubmit' => true,
    'options' => ['enctype' => 'multipart/form-data'],
    //'focus' => [$profileForm, 'username'],
]); ?>

    <?php echo $form->errorSummary($profileForm); ?>

<?= $this->render('/default/_form', [
    'form' => $form,
    'model' => $profileForm,
    'passwordForm' => $passwordForm,
]); ?>

<?php if (Yii::$app->user->can('usr.update.auth') && !empty($allRoles)): ?>
    <div class="control-group">
        <?php echo Html::label(Yii::t('manager', 'Authorization roles'), 'roles'); ?>
        <?php echo Html::checkBoxList(
            'roles',
            array_keys($assignedRoles),
            \yii\helpers\ArrayHelper::map($allRoles, 'name', 'description'),
            ['template' => '{beginLabel}{input}{labelTitle}{endLabel}']
        ); ?>
    </div>
<?php endif; ?>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton(
                $id === null ? Yii::t('manager', 'Create') : Yii::t('manager', 'Save'),
                ['class' => $module->submitButtonCssClass]
            ) ?>
        </div>
    </div>

<?php ActiveForm::end(); ?>

</div><!-- form -->
