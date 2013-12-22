<?php
return [
	'databases' => [
		'sqlite' => [
			'dsn' => 'sqlite::memory:',
			'fixture' => __DIR__ . '/fixtures/sqlite.sql',
		],
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
			'mail' => [
				'class' => 'yii\swiftmailer\Mailer',
				'useFileTransport' => true,
			],
		],
	],
];
