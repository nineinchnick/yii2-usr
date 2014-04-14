<?php
/* @var $this ManagerController */
/* @var $profileForm ProfileForm */
/* @var $passwordForm PasswordForm */
/* @var $identity CUserIdentity */
/* @var $authManager CAuthManager */
$identity = $profileForm->getIdentity();
$authManager = Yii::app()->authManager;
$assignedRoles = $id === null ? array() : $authManager->getAuthItems(CAuthItem::TYPE_ROLE, $id);
$allRoles = $authManager->getAuthItems(CAuthItem::TYPE_ROLE);

$this->pageTitle = $id === null ? Yii::t('UsrModule.manager', 'Create user') : Yii::t('UsrModule.manager', 'Update user {id}', array('{id}' => $id));

$this->menu=array(
    array('label'=>Yii::t('UsrModule.manager', 'List users'), 'url'=>array('index')),
);
if ($id !== null) {
    $this->menu[] = array('label'=>Yii::t('UsrModule.manager', 'Create user'), 'url'=>array('update'));
}

?>

<h1><?php echo $this->pageTitle; ?></h1>

<?php $this->widget('usr.components.UsrAlerts', array('cssClassPrefix'=>$this->module->alertCssClassPrefix)); ?>

<div class="<?php echo $this->module->formCssClass; ?>">

<?php if ($id !== null): ?>
<?php $this->widget($this->module->detailViewClass, array(
    'data' => $identity,
    'attributes' => array(
        array(
            'name' => 'createdOn',
            'type' => 'datetime',
            'label' => Yii::t('UsrModule.manager','Created On'),
            'value' => $identity->getTimestamps("createdOn"),
        ),
        array(
            'name' => 'updatedOn',
            'type' => 'datetime',
            'label' => Yii::t('UsrModule.manager','Updated On'),
            'value' => $identity->getTimestamps("updatedOn"),
        ),
        array(
            'name' => 'lastVisitOn',
            'type' => 'datetime',
            'label' => Yii::t('UsrModule.manager','Last Visit On'),
            'value' => $identity->getTimestamps("lastVisitOn"),
        ),
        array(
            'name' => 'passwordSetOn',
            'type' => 'datetime',
            'label' => Yii::t('UsrModule.manager','Password Set On'),
            'value' => $identity->getTimestamps("passwordSetOn"),
        ),
        array(
            'name'=>'emailVerified',
            'type'=>'raw',
            'label'=>Yii::t('UsrModule.manager', 'Email Verified'),
            'value'=>CHtml::link($identity->isVerified() ? Yii::t("UsrModule.manager", "Yes") : Yii::t("UsrModule.manager", "No"), array("verify", "id"=>$identity->id), array("class"=>"actionButton", "title"=>Yii::t("UsrModule.manager", "Toggle"))),
        ),
        array(
            'name'=>'isActive',
            'type'=>'raw',
            'label'=>Yii::t('UsrModule.manager', 'Is Active'),
            'value'=>CHtml::link($identity->isActive() ? Yii::t("UsrModule.manager", "Yes") : Yii::t("UsrModule.manager", "No"), array("activate", "id"=>$identity->id), array("class"=>"actionButton", "title"=>Yii::t("UsrModule.manager", "Toggle"))),
        ),
        array(
            'name'=>'isDisabled',
            'type'=>'raw',
            'label'=>Yii::t('UsrModule.manager', 'Is Disabled'),
            'value'=>CHtml::link($identity->isDisabled() ? Yii::t("UsrModule.manager", "Yes") : Yii::t("UsrModule.manager", "No"), array("disable", "id"=>$identity->id), array("class"=>"actionButton", "title"=>Yii::t("UsrModule.manager", "Toggle"))),
        ),
    ),
)); ?>
<?php endif; ?>

<?php $form=$this->beginWidget('CActiveForm', array(
    'id'=>'profile-form',
    'enableAjaxValidation'=>true,
    'enableClientValidation'=>false,
    'clientOptions'=>array(
        'validateOnSubmit'=>true,
    ),
    'htmlOptions' => array('enctype' => 'multipart/form-data'),
    'focus'=>array($profileForm,'username'),
)); ?>

    <p class="note"><?php echo Yii::t('UsrModule.manager', 'Fields with {asterisk} are required.', array('{asterisk}'=>'<span class="required">*</span>')); ?></p>

    <?php echo $form->errorSummary($profileForm); ?>

<?php $this->renderPartial('/default/_form', array('form'=>$form, 'model'=>$profileForm, 'passwordForm'=>$passwordForm)); ?>

<?php if (Yii::app()->user->checkAccess('usr.update.auth') && !empty($allRoles)): ?>
    <div class="control-group">
        <?php echo CHtml::label(Yii::t('UsrModule.manager', 'Authorization roles'), 'roles'); ?>
        <?php echo CHtml::checkBoxList('roles', array_keys($assignedRoles), CHtml::listData($allRoles, 'name', 'description'), array('template'=>'{beginLabel}{input}{labelTitle}{endLabel}')); ?>
    </div>
<?php endif; ?>

    <div class="buttons">
        <?php echo CHtml::submitButton($id === null ? Yii::t('UsrModule.manager', 'Create') : Yii::t('UsrModule.manager', 'Save'), array('class'=>$this->module->submitButtonCssClass)); ?>
    </div>

<?php $this->endWidget(); ?>

</div><!-- form -->
