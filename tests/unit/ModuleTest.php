<?php

namespace nineinchnick\usr\tests\unit;

use nineinchnick\usr\tests\TestCase as TestCase;
use nineinchnick\usr\Module as Module;

class ModuleTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication($this->getParam('app'), '\yii\console\Application');
    }

    public function testCreateForm()
    {
        $module = new Module('usr', \Yii::$app);
        $module->loginFormBehaviors = [
            'expiredPasswordBehavior' => [
                'class' => '\nineinchnick\usr\components\ExpiredPasswordBehavior',
                'passwordTimeout' => 300,
            ],
        ];
        $form = $module->createFormModel('LoginForm');
        $this->assertTrue($form->getBehavior('expiredPasswordBehavior') instanceof \nineinchnick\usr\components\ExpiredPasswordBehavior);
    }
}
