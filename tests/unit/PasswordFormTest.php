<?php

Yii::import('vendors.nineinchnick.yii-usr.tests.User');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserIdentity');
Yii::import('vendors.nineinchnick.yii-usr.models.BaseUsrForm');
Yii::import('vendors.nineinchnick.yii-usr.models.PasswordForm');

class PasswordFormTest extends CDbTestCase
{
	public $fixtures=array(
		'users'=>'User',
	);

	public static function validDataProvider() {
		return array(
			array(
				'scenario' => '',
				'attributes' => array(
					'password'=>'Test1233',
					'newPassword'=>'Test1234',
					'newVerify'=>'Test1234',
				),
			),
		);
	}

	public static function invalidDataProvider() {
		return array(
			array(
				'scenario' => '',
				'attributes' => array(
					'password'=>'xx',
					'newPassword'=>'oo',
					'newPasswordVerify'=>'oo',
				),
				'errors ' => array(
					'password' => array('Invalid password.'),
					'newVerify' => array('Verify cannot be blank.', 'Please type the same new password twice to verify it.'),
					'newPassword' => array('New password is too short (minimum is 8 characters).', 'New password must contain at least one lower and upper case character and a digit.'),
				),
			),
		);
	}

	public static function allDataProvider() {
		return array_merge(self::validDataProvider(), self::invalidDataProvider());
	}

	/**
	 * @dataProvider validDataProvider
	 */
	public function testValid($scenario, $attributes)
	{
		$form = new PasswordForm($scenario);
		$form->userIdentityClass = 'UserIdentity';
		$form->setIdentity(new UserIdentity('neo', 'Test1233'));
		$form->setAttributes($attributes);
		$this->assertTrue($form->validate(), 'Failed with following validation errors: '.print_r($form->getErrors(),true));
		$this->assertEmpty($form->getErrors());
	}


	/**
	 * @dataProvider invalidDataProvider
	 */
	public function testInvalid($scenario, $attributes, $errors)
	{
		$form = new PasswordForm($scenario);
		$form->userIdentityClass = 'UserIdentity';
		$form->setIdentity(new UserIdentity('neo', 'Test1233'));
		$form->setAttributes($attributes);
		$this->assertFalse($form->validate());
		$this->assertEquals($errors, $form->getErrors());
	}
}
