<?php

// change the following paths if necessary
$yiit=dirname(__FILE__).'/../../../yiisoft/yii/framework/yiit.php';
$config=dirname(__FILE__).'/config.php';

require_once($yiit);

Yii::createWebApplication($config);
