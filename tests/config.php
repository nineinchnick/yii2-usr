<?php
return [
	'db' => [
		'class' => 'yii\db\Connection',
		'dsn' => 'sqlite::memory:',
		'tablePrefix' => '',
		'charset' => 'utf8',
		//'on '.yii\db\Connection::EVENT_AFTER_OPEN => function($event){$event->sender->createCommand('PRAGMA foreign_keys = ON')->execute();},
	],
	'app' => [
		'preload' => ['usr'],
		'modules' => [
			'usr' => [
				'class' => 'nineinchnick\usr\Module',
				'captcha' => true,
				'oneTimePasswordMode' => 'time',
				'passwordTimeout' => 1,
			],
		],
		'components' => [
			'user' => ['identityClass' => 'nineinchnick\usr\tests\User'],
			'i18n' => [
				'translations' => [
					'models' => [
						'class' => 'yii\i18n\PhpMessageSource',
						'sourceLanguage' => 'en-US',
						'basePath' => '@app/messages',
					],
				],
			],
		],
	],
];
