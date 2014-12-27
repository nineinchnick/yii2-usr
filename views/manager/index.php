<?php
/* @var $this ManagerController */
/* @var $model SearchForm */

$this->pageTitle = Yii::t('UsrModule.manager', 'List users');

$this->menu = [
    ['label' => Yii::t('UsrModule.manager', 'List users'), 'url' => ['index']],
    ['label' => Yii::t('UsrModule.manager', 'Create user'), 'url' => ['update']],
];

$booleanFilter = ['0' => Yii::t('UsrModule.manager', 'No'), '1' => Yii::t('UsrModule.manager', 'Yes')];

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
Yii::app()->clientScript->registerScript('search', $script);

$csrf = !Yii::app()->request->enableCsrfValidation ? '' : "\n\t\tdata:{ '".Yii::app()->request->csrfTokenName."':'".Yii::app()->request->csrfToken."' },";
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
Yii::app()->clientScript->registerScript('actions', $script);
?>

<h1><?php echo $this->pageTitle; ?></h1>

<?php $this->widget('usr.components.UsrAlerts', ['cssClassPrefix' => $this->module->alertCssClassPrefix]); ?>

<p>
<?php echo Yii::t('UsrModule.manager', 'You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b> or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.'); ?>
</p>

<?php echo CHtml::link(Yii::t('UsrModule.manager', 'Advanced Search'), '#', ['class' => 'search-button']); ?>
<div class="search-form" style="display:none">
<?= $this->render('_search', ['model' => $model]); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', [
    'id' => 'identity-grid',
    'dataProvider' => $model->getIdentity()->getDataProvider($model),
    'filter' => $model,
    'columns' => [
        'id:number:'.Yii::t('UsrModule.manager', 'ID'),
        'username:text:'.Yii::t('UsrModule.manager', 'Username'),
        'email:text:'.Yii::t('UsrModule.manager', 'Email'),
        'firstName:text:'.Yii::t('UsrModule.manager', 'Firstname'),
        'lastName:text:'.Yii::t('UsrModule.manager', 'Lastname'),
        /*array(
            'name' => 'createdOn',
            'type' => 'datetime',
            'header' => Yii::t('UsrModule.manager','Created On'),
            'value' => '$data->getTimestamps("createdOn")',
        ),*/
        [
            'name' => 'updatedOn',
            'type' => 'datetime',
            'header' => Yii::t('UsrModule.manager', 'Updated On'),
            'value' => '$data->getTimestamps("updatedOn")',
        ],
        [
            'name' => 'lastVisitOn',
            'type' => 'datetime',
            'header' => Yii::t('UsrModule.manager', 'Last Visit On'),
            'value' => '$data->getTimestamps("lastVisitOn")',
        ],
        [
            'name' => 'emailVerified',
            'type' => 'raw',
            'header' => Yii::t('UsrModule.manager', 'Email Verified'),
            'filter' => $booleanFilter,
            'value' => 'CHtml::link($data->isVerified() ? Yii::t("UsrModule.manager", "Verified") : Yii::t("UsrModule.manager", "Unverified"), array("verify", "id"=>$data->id), array("class"=>"actionButton", "title"=>Yii::t("UsrModule.manager", "Toggle")))',
        ],
        [
            'name' => 'isActive',
            'type' => 'raw',
            'header' => Yii::t('UsrModule.manager', 'Is Active'),
            'filter' => $booleanFilter,
            'value' => 'CHtml::link($data->isActive() ? Yii::t("UsrModule.manager", "Active") : Yii::t("UsrModule.manager", "Not active"), array("activate", "id"=>$data->id), array("class"=>"actionButton", "title"=>Yii::t("UsrModule.manager", "Toggle")))',
        ],
        [
            'name' => 'isDisabled',
            'type' => 'raw',
            'header' => Yii::t('UsrModule.manager', 'Is Disabled'),
            'filter' => $booleanFilter,
            'value' => 'CHtml::link($data->isDisabled() ? Yii::t("UsrModule.manager", "Disabled") : Yii::t("UsrModule.manager", "Enabled"), array("disable", "id"=>$data->id), array("class"=>"actionButton", "title"=>Yii::t("UsrModule.manager", "Toggle")))',
        ],
        [
            'class' => 'CButtonColumn',
            'template' => '{update} {delete}', // {activate} {deactivate} {enable} {disable} {verify} {unverify}',
            'viewButtonUrl' => 'Yii::app()->controller->createUrl("view",array("id"=>$data->id))',
            'updateButtonUrl' => 'Yii::app()->controller->createUrl("update",array("id"=>$data->id))',
            'deleteButtonUrl' => 'Yii::app()->controller->createUrl("delete",array("id"=>$data->id))',
            'buttons' => [
                    //'imageUrl'=>'...',  // image URL of the button. If not set or false, a text link is used
                    //'options'=>array(...), // HTML options for the button tag
                    //'click'=>'...',     // a JS function to be invoked when the button is clicked
                /*'activate' => array(
                    'label'=>Yii::t('UsrModule.manager', 'Activate'),
                    'url'=>'Yii::app()->controller->createUrl("activate",array("id"=>$data->id))',
                    'visible'=>'!$data->isActive()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'deactivate' => array(
                    'label'=>Yii::t('UsrModule.manager', 'Deactivate'),
                    'url'=>'Yii::app()->controller->createUrl("activate",array("id"=>$data->id))',
                    'visible'=>'$data->isActive()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'enable' => array(
                    'label'=>Yii::t('UsrModule.manager', 'Enable'),
                    'url'=>'Yii::app()->controller->createUrl("enable",array("id"=>$data->id))',
                    'visible'=>'$data->isDisabled()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'disable' => array(
                    'label'=>Yii::t('UsrModule.manager', 'Disable'),
                    'url'=>'Yii::app()->controller->createUrl("enable",array("id"=>$data->id))',
                    'visible'=>'!$data->isDisabled()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'verify' => array(
                    'label'=>Yii::t('UsrModule.manager', 'Verify'),
                    'url'=>'Yii::app()->controller->createUrl("verify",array("id"=>$data->id))',
                    'visible'=>'!$data->isVerified()',
                    'options'=>array('class'=>'actionButton'),
                ),
                'unverify' => array(
                    'label'=>Yii::t('UsrModule.manager', 'Unverify'),
                    'url'=>'Yii::app()->controller->createUrl("verify",array("id"=>$data->id))',
                    'visible'=>'$data->isVerified()',
                    'options'=>array('class'=>'actionButton'),
                ),*/
            ],
        ],
    ],
]); ?>
