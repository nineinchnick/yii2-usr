<?php

namespace nineinchnick\usr\tests\unit;

use nineinchnick\usr\tests\DatabaseTestCase as DatabaseTestCase;
use nineinchnick\usr\models;

class RecoveryFormTest extends DatabaseTestCase
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
                    'username' => 'neo',
                    'email' => 'neo@matrix.com',
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
                    'username' => 'trin',
                    'email' => 'trinity@matrix.com',
                ],
                'errors ' => [
                    'username' => ['No user found matching this username.'],
                ],
            ],
        ];
    }

    public static function allDataProvider()
    {
        return array_merge(self::validDataProvider(), self::invalidDataProvider());
    }

    public function testWithBehavior()
    {
        $form = new models\RecoveryForm();
        $formAttributes = $form->attributes();
        $formRules = $form->rules();
        $formLabels = $form->attributeLabels();
        $form->attachBehavior('captcha', ['class' => 'nineinchnick\usr\components\CaptchaFormBehavior']);
        $behaviorAttributes = $form->getBehavior('captcha')->attributes();
        $behaviorRules = $form->getBehavior('captcha')->rules();
        $behaviorLabels = $form->getBehavior('captcha')->attributeLabels();
        $this->assertEquals(array_merge($formAttributes, $behaviorAttributes), $form->attributes());
        $this->assertEquals(array_merge($behaviorRules, $formRules), $form->rules());
        $this->assertEquals(array_merge($formLabels, $behaviorLabels), $form->attributeLabels());
        $form->detachBehavior('captcha');
        $this->assertEquals($formAttributes, $form->attributes());
        $this->assertEquals($formAttributes, $form->attributes());
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testValid($scenario, $attributes)
    {
        $form = new models\RecoveryForm();
        if ($scenario != '') {
            $form->setScenario($scenario);
        }
        $form->setAttributes($attributes);
        $this->assertTrue($form->validate(), 'Failed with following validation errors: '.print_r($form->getErrors(), true));
        $this->assertEmpty($form->getErrors());
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testInvalid($scenario, $attributes, $errors)
    {
        $form = new models\RecoveryForm();
        if ($scenario != '') {
            $form->setScenario($scenario);
        }
        $form->setAttributes($attributes);
        $this->assertFalse($form->validate());
        $this->assertEquals($errors, $form->getErrors());
    }
}
