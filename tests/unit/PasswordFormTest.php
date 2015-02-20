<?php

namespace nineinchnick\usr\tests\unit;

use nineinchnick\usr\tests\DatabaseTestCase as DatabaseTestCase;
use nineinchnick\usr\models;
use nineinchnick\usr\tests\User;

class PasswordFormTest extends DatabaseTestCase
{
    public $fixtures = [
        'users' => 'User',
    ];

    public static function validDataProvider()
    {
        return [
            [
                'scenario' => '',
                'attributes' => [
                    'password' => 'Test1233',
                    'newPassword' => 'Test1234',
                    'newVerify' => 'Test1234',
                ],
            ],
        ];
    }

    public static function invalidDataProvider()
    {
        return [
            [
                'scenario' => '',
                'attributes' => [
                    'password' => 'xx',
                    'newPassword' => 'oo',
                    'newPasswordVerify' => 'oo',
                ],
                'errors ' => [
                    'password' => ['Invalid password.'],
                    'newVerify' => ['Verify cannot be blank.'],
                    'newPassword' => ['New password should contain at least 8 characters.'],
                ],
            ],
        ];
    }

    public static function allDataProvider()
    {
        return array_merge(self::validDataProvider(), self::invalidDataProvider());
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testValid($scenario, $attributes)
    {
        $form = new models\PasswordForm($scenario);
        $form->setIdentity(User::findOne(['username' => 'neo']));
        $form->setAttributes($attributes);
        $this->assertTrue($form->validate(), 'Failed with following validation errors: '.print_r($form->getErrors(), true));
        $this->assertEmpty($form->getErrors());
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testInvalid($scenario, $attributes, $errors)
    {
        $form = new models\PasswordForm($scenario);
        $form->setIdentity(User::findOne(['username' => 'neo']));
        $form->setAttributes($attributes);
        $this->assertFalse($form->validate());
        $this->assertEquals($errors, $form->getErrors());
    }
}
