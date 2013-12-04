<?php

return array(
	'basePath'		=> dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'			=> 'Widgets and Extensions demo',
	'aliases'		=> array(
		'vendors' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..',
	),
	'modules'=>array(
		'usr'=>array(
			'class'=>'vendors.nineinchnick.yii-usr.UsrModule',
			'userIdentityClass' => 'UserIdentity',
			'oneTimePasswordMode' => 'time',
			'captcha' => array('clickableImage'=>true,'showRefreshButton'=>false),
		),
	),
	'components'=>array(
		'db'=>array(
			'connectionString' => 'sqlite::memory:',
			'initSQLs' => array('PRAGMA foreign_keys = ON'),
			//'connectionString' => 'mysql:host=localhost;dbname=test',
			'tablePrefix' => 'tbl_',
			'enableParamLogging'=>true,
		),
		'fixture'=>array(
			'class'=>'system.test.CDbFixtureManager',
		),
	),
);
