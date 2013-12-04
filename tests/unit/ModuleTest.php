<?php

Yii::import('vendors.nineinchnick.yii-usr.UsrModule');

class ModuleTest extends CTestCase
{
	public function testModule()
	{
		$module = new UsrModule('usr',Yii::app());
		$composer = json_decode(file_get_contents(dirname(__FILE__).'/../../composer.json'));
		$this->assertEquals($module->getVersion(), $composer->version);
	}

	public function testCreateForm()
	{
		$module = new UsrModule('usr',Yii::app());
		$module->passwordTimeout = 300;
		$form = $module->createFormModel('LoginForm');
		$this->assertTrue($form->asa('expiredPasswordBehavior') instanceof ExpiredPasswordBehavior);
	}
}
