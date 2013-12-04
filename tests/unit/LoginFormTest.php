<?php

Yii::import('vendors.nineinchnick.yii-usr.tests.User');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserIdentity');
Yii::import('vendors.nineinchnick.yii-usr.models.BaseUsrForm');
Yii::import('vendors.nineinchnick.yii-usr.models.LoginForm');

class LoginFormTest extends CDbTestCase
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
					'password'=>'Test1233',
				),
			),
		);
	}

	public static function invalidDataProvider() {
		return array(
			array(
				'scenario' => '',
				'attributes' => array(
					'username'=>'',
					'password'=>'',
				),
				'errors ' => array(
					'username'=>array('Username cannot be blank.'),
					'password'=>array('Password cannot be blank.'),
				),
			),
			array(
				'scenario' => '',
				'attributes' => array(
					'username'=>'neo',
					'password'=>'xx',
				),
				'errors' => array(
					'password'=>array('Invalid username or password.'),
				),
			),
		);
	}

	public static function allDataProvider() {
		return array_merge(self::validDataProvider(), self::invalidDataProvider());
	}

	public function testWithBehavior()
	{
		$form = new LoginForm;
		$formAttributes = $form->attributeNames();
		$formRules = $form->rules();
		$formLabels = $form->attributeLabels();
		$form->attachBehavior('captcha', array('class' => 'CaptchaFormBehavior'));
		$behaviorAttributes = $form->asa('captcha')->attributeNames();
		$behaviorRules = $form->asa('captcha')->rules();
		$behaviorLabels = $form->asa('captcha')->attributeLabels();
		$this->assertEquals(array_merge($formAttributes, $behaviorAttributes), $form->attributeNames());
		$this->assertEquals(array_merge($formRules, $behaviorRules), $form->rules());
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
		$form = new LoginForm($scenario);
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
		$form = new LoginForm($scenario);
		$form->userIdentityClass = 'UserIdentity';
		$form->setAttributes($attributes);
		$this->assertFalse($form->validate());
		$this->assertEquals($errors, $form->getErrors());
	}
}
