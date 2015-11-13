<?php

use nineinchnick\usr\components;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var \nineinchnick\usr\models\SearchForm $model
 * @var ActiveForm $form
 */
$this->title = Yii::t('manager', 'List users');
$this->params['breadcrumbs'][] = $this->title;
$this->params['menu'] = [
    ['label' => Yii::t('manager', 'List users'), 'url' => ['index']],
    ['label' => Yii::t('manager', 'Create user'), 'url' => ['update']],
];

$booleanFilter = ['0' => Yii::t('manager', 'No'), '1' => Yii::t('manager', 'Yes')];

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

?>

<h1><?= Html::encode($this->title) ?></h1>

<?= components\Alerts::widget() ?>

<p>
<?= Yii::t('manager', 'You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b> or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.'); ?>
</p>

<?= Html::a(Yii::t('manager', 'Advanced Search'), '#', ['class' => 'search-button']); ?>
<div class="search-form" style="display:none">
<?= $this->render('_search', ['model' => $model]); ?>
</div><!-- search-form -->

<?= \yii\grid\GridView::widget([
    'id' => 'identity-grid',
    'dataProvider' => $model->getIdentity()->getDataProvider($model),
    'filterModel' => $model,
    'columns' => [
        'id:integer:'.Yii::t('manager', 'ID'),
        'username:text:'.Yii::t('manager', 'Username'),
        'email:text:'.Yii::t('manager', 'Email'),
        'firstname:text:'.Yii::t('manager', 'Firstname'),
        'lastname:text:'.Yii::t('manager', 'Lastname'),
        /*[
            'attribute' => 'createdOn',
            'format' => 'datetime',
            'label' => Yii::t('manager','Created On'),
            'value' => '$data->getTimestamps("createdOn")',
        ],*/
        [
            'attribute' => 'updatedOn',
            'format' => 'datetime',
            'label' => Yii::t('manager', 'Updated On'),
            'value' => function ($data) {
                return $data->getTimestamps("updatedOn");
            },
        ],
        [
            'attribute' => 'lastVisitOn',
            'format' => 'datetime',
            'label' => Yii::t('manager', 'Last Visit On'),
            'value' => function ($data) {
                return $data->getTimestamps("lastVisitOn");
            },
        ],
        [
            'attribute' => 'emailVerified',
            'format' => 'raw',
            'label' => Yii::t('manager', 'Email Verified'),
            'filter' => $booleanFilter,
            'value' => function ($data) {
                return Html::a(
                    $data->isVerified() ? Yii::t("manager", "Verified") : Yii::t("manager", "Unverified"),
                    ["verify", "id" => $data->id],
                    ["class" => "actionButton", "title" => Yii::t("manager", "Toggle")]
                );
            },
        ],
        [
            'attribute' => 'isActive',
            'format' => 'raw',
            'label' => Yii::t('manager', 'Is Active'),
            'filter' => $booleanFilter,
            'value' => function ($data) {
                return Html::a(
                    $data->isActive() ? Yii::t("manager", "Active") : Yii::t("manager", "Not active"),
                    ["activate", "id" => $data->id],
                    ["class" => "actionButton", "title" => Yii::t("manager", "Toggle")]
                );
            },
        ],
        [
            'attribute' => 'isDisabled',
            'format' => 'raw',
            'label' => Yii::t('manager', 'Is Disabled'),
            'filter' => $booleanFilter,
            'value' => function ($data) {
                return Html::a(
                    $data->isDisabled() ? Yii::t("manager", "Disabled") : Yii::t("manager", "Enabled"),
                    ["disable", "id" => $data->id],
                    ["class" => "actionButton", "title" => Yii::t("manager", "Toggle")]
                );
            },
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{update} {delete}', // {activate} {deactivate} {enable} {disable} {verify} {unverify}',
            'urlCreator' => function ($action, $model, $key, $index) {
                return \yii\helpers\Url::toRoute([$action, 'id' => $model->primaryKey]);
            },
            'buttons' => [
            ],
        ],
    ],
]); ?>
