<?php
return [
    'db' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'sqlite::memory:',
        'tablePrefix' => '',
        'charset' => 'utf8',
        //'on '.yii\db\Connection::EVENT_AFTER_OPEN => function ($event) {$event->sender->createCommand('PRAGMA foreign_keys = ON')->execute();},
    ],
    'app' => [
        'bootstrap' => ['usr'],
        'modules' => [
            'usr' => [
                'class' => 'nineinchnick\usr\Module',
                'captcha' => true,
                'loginFormBehaviors' => [
                    'oneTimePasswordBehavior' => [
                        'class' => '\nineinchnick\usr\components\OneTimePasswordFormBehavior',
                        'mode' => 'time',
                    ],
                    'expiredPasswordBehavior' => [
                        'class' => '\nineinchnick\usr\components\ExpiredPasswordBehavior',
                        'passwordTimeout' => 1,
                    ],
                ],
                'pictureUploadRules' => [
                    ['file', 'skipOnEmpty' => true, 'extensions'=>'jpg, gif, png', 'maxSize'=>2*1024*1024, 'maxFiles' => 1],
                ],
            ],
        ],
        'components' => [
            'user' => [
                'class' => 'yii\web\User',
                'identityClass' => 'nineinchnick\usr\tests\User',
                'enableSession' => false,
            ],
            'mail' => [
                'class' => 'yii\swiftmailer\Mailer',
                'useFileTransport' => true,
                'messageConfig' => [
                    'from' => 'admin@demo2.niix.pl',
                ],
            ],
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
