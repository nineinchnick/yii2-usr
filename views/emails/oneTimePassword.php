<p>
    This message contains a one time password. It was requested on the <?= \yii\helpers\Html::a(Yii::$app->name, $siteUrl); ?>. If you did not performed this request, please ignore this email or contact our administrator.
</p>

<p>Enter this code on the page that requested it:</p>
<h3><?= $code; ?></h3>
