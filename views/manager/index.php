<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var models\SearchForm $model
 * @var ActiveForm $form
 */

$this->title = Yii::t('manager', 'List users');

$this->context->menu = array(
    array('label'=>Yii::t('manager', 'List users'), 'url'=>array('index')),
    array('label'=>Yii::t('manager', 'Create user'), 'url'=>array('update')),
);

$booleanFilter = array('0'=>Yii::t('manager', 'No'), '1'=>Yii::t('manager', 'Yes'));

$script = <<<JavaScript
$('.search-button').click(function () {
    $('.search-form').toggle();

    return false;
});
$('.search-form form').submit(function () {
    $('#identity-grid').yiiGridView('update', {data: $(this).serialize()});

    return false;
});
JavaScript;
$this->registerJs($script);

$csrf = '';// !Yii::$app->request->enableCsrfValidation ? '' : "\n\t\tdata:{ '".Yii::$app->request->csrfTokenName."':'".Yii::$app->request->csrfToken."' },";
$script = <<<JavaScript
var ajaxAction = function () {
    jQuery('#identity-grid').yiiGridView('update', {
        type: 'POST',
        url: jQuery(this).attr('href'),$csrf
        success: function (data) {jQuery('#identity-grid').yiiGridView('update');}
    });

    return false;
};
jQuery('#identity-grid').on('click', 'a.actionButton', ajaxAction);
JavaScript;
$this->registerJs($script);
?>

<h1><?php echo Html::encode($this->title); ?></h1>

<?= components\Alerts::widget() ?>

<p>
<?php echo Yii::t('manager', 'You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b> or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.'); ?>
</p>

<?php echo Html::a(Yii::t('manager', 'Advanced Search'),'#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<?php echo $this->render('_search',array('model'=>$model)); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', array(
    'id'=>'identity-grid',
    'dataProvider'=>$model->getIdentity()->getDataProvider($model),
    'filter'=>$model,
    'columns'=>array(
        'id:number:'.Yii::t('manager','ID'),
        'username:text:'.Yii::t('manager','Username'),
        'email:text:'.Yii::t('manager','Email'),
        'firstName:text:'.Yii::t('manager','Firstname'),
        'lastName:text:'.Yii::t('manager','Lastname'),
        /*array(
            'name' => 'createdOn',
            'type' => 'datetime',
            'header' => Yii::t('manager','Created On'),
            'value' => '$data->getTimestamps("createdOn")',
        ),*/
        array(
            'name' => 'updatedOn',
            'type' => 'datetime',
            'header' => Yii::t('manager','Updated On'),
            'value' => '$data->getTimestamps("updatedOn")',
        ),
        array(
            'name' => 'lastVisitOn',
            'type' => 'datetime',
            'header' => Yii::t('manager','Last Visit On'),
            'value' => '$data->getTimestamps("lastVisitOn")',
        ),
        array(
            'name'=>'emailVerified',
            'type'=>'raw',
            'header'=>Yii::t('manager', 'Email Verified'),
            'filter'=>$booleanFilter,
            'value'=>'CHtml::link($data->isVerified() ? Yii::t("manager", "Verified") : Yii::t("manager", "Unverified"), array("verify", "id"=>$data->id), array("class"=>"actionButton", "title"=>Yii::t("manager", "Toggle")))',
        ),
        array(
            'name'=>'isActive',
            'type'=>'raw',
            'header'=>Yii::t('manager', 'Is Active'),
            'filter'=>$booleanFilter,
            'value'=>'CHtml::link($data->isActive() ? Yii::t("manager", "Active") : Yii::t("manager", "Not active"), array("activate", "id"=>$data->id), array("class"=>"actionButton", "title"=>Yii::t("manager", "Toggle")))',
        ),
        array(
            'name'=>'isDisabled',
            'type'=>'raw',
            'header'=>Yii::t('manager', 'Is Disabled'),
            'filter'=>$booleanFilter,
            'value'=>'CHtml::link($data->isDisabled() ? Yii::t("manager", "Disabled") : Yii::t("manager", "Enabled"), array("disable", "id"=>$data->id), array("class"=>"actionButton", "title"=>Yii::t("manager", "Toggle")))',
        ),
        array(
            'class'=>'CButtonColumn',
            'template'=>'{update} {delete}',// {activate} {deactivate} {enable} {disable} {verify} {unverify}',
            'viewButtonUrl'=>'Yii::app()->controller->createUrl("view",array("id"=>$data->id))',
            'updateButtonUrl'=>'Yii::app()->controller->createUrl("update",array("id"=>$data->id))',
            'deleteButtonUrl'=>'Yii::app()->controller->createUrl("delete",array("id"=>$data->id))',
            'buttons' => array(
                    //'imageUrl'=>'...',  // image URL of the button. If not set or false, a text link is used
                    //'options'=>array(...), // HTML options for the button tag
                    //'click'=>'...',     // a JS function to be invoked when the button is clicked
                /*'activate' => array(
                    'label'=>Yii::t('manager', 'Activate'),
                    'url'=>'Yii::app()->controller->createUrl("activate",array("id"=>$data->id))',
                    'visible'=>'!$data->isActive()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'deactivate' => array(
                    'label'=>Yii::t('manager', 'Deactivate'),
                    'url'=>'Yii::app()->controller->createUrl("activate",array("id"=>$data->id))',
                    'visible'=>'$data->isActive()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'enable' => array(
                    'label'=>Yii::t('manager', 'Enable'),
                    'url'=>'Yii::app()->controller->createUrl("enable",array("id"=>$data->id))',
                    'visible'=>'$data->isDisabled()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'disable' => array(
                    'label'=>Yii::t('manager', 'Disable'),
                    'url'=>'Yii::app()->controller->createUrl("enable",array("id"=>$data->id))',
                    'visible'=>'!$data->isDisabled()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'verify' => array(
                    'label'=>Yii::t('manager', 'Verify'),
                    'url'=>'Yii::app()->controller->createUrl("verify",array("id"=>$data->id))',
                    'visible'=>'!$data->isVerified()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'unverify' => array(
                    'label'=>Yii::t('manager', 'Unverify'),
                    'url'=>'Yii::app()->controller->createUrl("verify",array("id"=>$data->id))',
                    'visible'=>'$data->isVerified()',
                    'options'=>array('class'=>'actionButton'),
                ),*/
            ),
        ),
    ),
)); ?>
