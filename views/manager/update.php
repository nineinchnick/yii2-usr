<?php
/* @var $this ManagerController */
/* @var $profileForm ProfileForm */
/* @var $passwordForm PasswordForm */
/* @var $identity CUserIdentity */
/* @var $authManager CAuthManager */
$identity = $profileForm->getIdentity();
$authManager = Yii::app()->authManager;
$assignedRoles = $id === null ? [] : $authManager->getAuthItems(CAuthItem::TYPE_ROLE, $id);
$allRoles = $authManager->getAuthItems(CAuthItem::TYPE_ROLE);

$this->pageTitle = $id === null ? Yii::t('UsrModule.manager', 'Create user') : Yii::t('UsrModule.manager', 'Update user {id}', ['{id}' => $id]);

$this->menu = [
    ['label' => Yii::t('UsrModule.manager', 'List users'), 'url' => ['index']],
];
if ($id !== null) {
    $this->menu[] = ['label' => Yii::t('UsrModule.manager', 'Create user'), 'url' => ['update']];
}

?>

<h1><?php echo $this->pageTitle; ?></h1>

<?php $this->widget('usr.components.UsrAlerts', ['cssClassPrefix' => $this->module->alertCssClassPrefix]); ?>

<div class="<?php echo $this->module->formCssClass; ?>">

<?php if ($id !== null): ?>
<?php $this->widget($this->module->detailViewClass, [
    'data' => $identity,
    'attributes' => [
        [
            'name' => 'createdOn',
            'type' => 'datetime',
            'label' => Yii::t('UsrModule.manager', 'Created On'),
            'value' => $identity->getTimestamps("createdOn"),
        ],
        [
            'name' => 'updatedOn',
            'type' => 'datetime',
            'label' => Yii::t('UsrModule.manager', 'Updated On'),
            'value' => $identity->getTimestamps("updatedOn"),
        ],
        [
            'name' => 'lastVisitOn',
            'type' => 'datetime',
            'label' => Yii::t('UsrModule.manager', 'Last Visit On'),
            'value' => $identity->getTimestamps("lastVisitOn"),
        ],
        [
            'name' => 'passwordSetOn',
            'type' => 'datetime',
            'label' => Yii::t('UsrModule.manager', 'Password Set On'),
            'value' => $identity->getTimestamps("passwordSetOn"),
        ],
        [
            'name' => 'emailVerified',
            'type' => 'raw',
            'label' => Yii::t('UsrModule.manager', 'Email Verified'),
            'value' => CHtml::link($identity->isVerified() ? Yii::t("UsrModule.manager", "Yes") : Yii::t("UsrModule.manager", "No"), ["verify", "id" => $identity->id], ["class" => "actionButton", "title" => Yii::t("UsrModule.manager", "Toggle")]),
        ],
        [
            'name' => 'isActive',
            'type' => 'raw',
            'label' => Yii::t('UsrModule.manager', 'Is Active'),
            'value' => CHtml::link($identity->isActive() ? Yii::t("UsrModule.manager", "Yes") : Yii::t("UsrModule.manager", "No"), ["activate", "id" => $identity->id], ["class" => "actionButton", "title" => Yii::t("UsrModule.manager", "Toggle")]),
        ],
        [
            'name' => 'isDisabled',
            'type' => 'raw',
            'label' => Yii::t('UsrModule.manager', 'Is Disabled'),
            'value' => CHtml::link($identity->isDisabled() ? Yii::t("UsrModule.manager", "Yes") : Yii::t("UsrModule.manager", "No"), ["disable", "id" => $identity->id], ["class" => "actionButton", "title" => Yii::t("UsrModule.manager", "Toggle")]),
        ],
    ],
]); ?>
<?php endif; ?>

<?php $form = $this->beginWidget('CActiveForm', [
    'id' => 'profile-form',
    'enableAjaxValidation' => true,
    'enableClientValidation' => false,
    'clientOptions' => [
        'validateOnSubmit' => true,
    ],
    'htmlOptions' => ['enctype' => 'multipart/form-data'],
    'focus' => [$profileForm, 'username'],
]); ?>

    <p class="note"><?php echo Yii::t('UsrModule.manager', 'Fields with {asterisk} are required.', ['{asterisk}' => '<span class="required">*</span>']); ?></p>

    <?php echo $form->errorSummary($profileForm); ?>

<?php $this->renderPartial('/default/_form', ['form' => $form, 'model' => $profileForm, 'passwordForm' => $passwordForm]); ?>

<?php if (Yii::app()->user->checkAccess('usr.update.auth') && !empty($allRoles)): ?>
    <div class="control-group">
        <?php echo CHtml::label(Yii::t('UsrModule.manager', 'Authorization roles'), 'roles'); ?>
        <?php echo CHtml::checkBoxList('roles', array_keys($assignedRoles), CHtml::listData($allRoles, 'name', 'description'), ['template' => '{beginLabel}{input}{labelTitle}{endLabel}']); ?>
    </div>
<?php endif; ?>

    <div class="buttons">
        <?php echo CHtml::submitButton($id === null ? Yii::t('UsrModule.manager', 'Create') : Yii::t('UsrModule.manager', 'Save'), ['class' => $this->module->submitButtonCssClass]); ?>
    </div>

<?php $this->endWidget(); ?>

</div><!-- form -->
