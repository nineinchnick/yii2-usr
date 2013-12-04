<?php

Yii::import('vendors.nineinchnick.yii-usr.tests.User');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserIdentity');
Yii::import('vendors.nineinchnick.yii-usr.models.BaseUsrForm');
Yii::import('vendors.nineinchnick.yii-usr.models.RecoveryForm');

class RecoveryFormTest extends CDbTestCase
{
	public $fixtures=array(
		'users'=>'User',
	);

	public static function validDataProvider() {
		return array(
			array(
				'scenario' => '',
				'attributes' => array(
					'username'=>'neo',
					'email'=>'neo@matrix.com',
				),
			),
		);
	}

	public static function invalidDataProvider() {
		return array(
			array(
				'scenario' => '',
				'attributes' => array(
					'username'=>'trin',
					'email'=>'trinity@matrix.com',
				),
				'errors ' => array(
					'username'=>array('No user found matching this username.'),
				),
			),
		);
	}

	public static function allDataProvider() {
		return array_merge(self::validDataProvider(), self::invalidDataProvider());
	}

	public function testWithBehavior()
	{
		$form = new RecoveryForm;
		$formAttributes = $form->attributeNames();
		$formRules = $form->rules();
		$formLabels = $form->attributeLabels();
		$form->attachBehavior('captcha', array('class' => 'CaptchaFormBehavior'));
		$behaviorAttributes = $form->asa('captcha')->attributeNames();
		$behaviorRules = $form->asa('captcha')->rules();
		$behaviorLabels = $form->asa('captcha')->attributeLabels();
		$this->assertEquals(array_merge($formAttributes, $behaviorAttributes), $form->attributeNames());
		$this->assertEquals(array_merge($behaviorRules, $formRules), $form->rules());
		$this->assertEquals(array_merge($formLabels, $behaviorLabels), $form->attributeLabels());
		$form->detachBehavior('captcha');
		$this->assertEquals($formAttributes, $form->attributeNames());
		$this->assertEquals($formAttributes, $form->attributeNames());
	}

	/**
	 * @dataProvider validDataProvider
	 */
	public function testValid($scenario, $attributes)
	{
		$form = new RecoveryForm($scenario);
		$form->userIdentityClass = 'UserIdentity';
		$form->setAttributes($attributes);
		$this->assertTrue($form->validate(), 'Failed with following validation errors: '.print_r($form->getErrors(),true));
		$this->assertEmpty($form->getErrors());
	}


	/**
	 * @dataProvider invalidDataProvider
	 */
	public function testInvalid($scenario, $attributes, $errors)
	{
		$form = new RecoveryForm($scenario);
		$form->userIdentityClass = 'UserIdentity';
		$form->setAttributes($attributes);
		$this->assertFalse($form->validate());
		$this->assertEquals($errors, $form->getErrors());
	}
}
