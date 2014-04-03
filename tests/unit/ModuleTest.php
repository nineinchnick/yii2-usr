<?php

namespace nineinchnick\usr\tests\unit;

use nineinchnick\usr\tests\TestCase as TestCase;
use nineinchnick\usr\Module as Module;

class ModuleTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication($this->getParam('app'), '\yii\web\Application');
    }

    public function testModule()
    {
        $module = new Module('usr',\Yii::$app);
        $composer = json_decode(file_get_contents(dirname(__FILE__).'/../../composer.json'));
        $this->assertEquals($module->getVersion(), $composer->version);
    }

    public function testCreateForm()
    {
        $module = new Module('usr',\Yii::$app);
        $module->passwordTimeout = 300;
        $form = $module->createFormModel('LoginForm');
        $this->assertTrue($form->getBehavior('expiredPasswordBehavior') instanceof \nineinchnick\usr\components\ExpiredPasswordBehavior);
    }
}
