<?php
/* @var $this ManagerController */
/* @var $model SearchForm */
/* @var $form CActiveForm */

$booleanData = [Yii::t('UsrModule.manager', 'No'), Yii::t('UsrModule.manager', 'Yes')];
$booleanOptions = ['empty' => Yii::t('UsrModule.manager', 'Any'), 'separator' => '', 'labelOptions' => ['style' => 'display: inline; float: none;']];
?>

<div class="wide form">

<?php $form = $this->beginWidget('CActiveForm', [
    'action' => Yii::app()->createUrl($this->route),
    'method' => 'get',
]); ?>

    <div class="control-group">
        <?php echo $form->label($model, 'id'); ?>
        <?php echo $form->textField($model, 'id'); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'username'); ?>
        <?php echo $form->textField($model, 'username', ['size' => 60, 'maxlength' => 255]); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'email'); ?>
        <?php echo $form->textField($model, 'email', ['size' => 60, 'maxlength' => 255]); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'firstName'); ?>
        <?php echo $form->textField($model, 'firstName', ['size' => 60, 'maxlength' => 255]); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'lastName'); ?>
        <?php echo $form->textField($model, 'lastName', ['size' => 60, 'maxlength' => 255]); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'createdOn'); ?>
        <?php echo $form->textField($model, 'createdOn'); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'updatedOn'); ?>
        <?php echo $form->textField($model, 'updatedOn'); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'lastVisitOn'); ?>
        <?php echo $form->textField($model, 'lastVisitOn'); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'emailVerified'); ?>
        <?php echo $form->radioButtonList($model, 'emailVerified', $booleanData, $booleanOptions); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'isActive'); ?>
        <?php echo $form->radioButtonList($model, 'isActive', $booleanData, $booleanOptions); ?>
    </div>

    <div class="control-group">
        <?php echo $form->label($model, 'isDisabled'); ?>
        <?php echo $form->radioButtonList($model, 'isDisabled', $booleanData, $booleanOptions); ?>
    </div>

    <div class="buttons">
        <?php echo CHtml::submitButton(Yii::t('UsrModule.manager', 'Search')); ?>
    </div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->
