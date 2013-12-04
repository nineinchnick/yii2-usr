<?php

Yii::import('vendors.nineinchnick.yii-usr.tests.User');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserRemoteIdentity');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserUsedPassword');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserIdentity');

class UserIdentityTest extends CDbTestCase
{
	public $fixtures=array(
		'users'=>'User',
		'user_used_passwords'=>'UserUsedPassword',
	);

	/**
	 */
	public function testGetSet()
	{
		$identity = new UserIdentity(null, null);
		$attributes = array(
			'username' => 'u1',
			'email' => 'e1',
			'firstName' => 'f1',
			'lastName' => 'l1',
		);
		$identity->setAttributes($attributes);
		$this->assertEquals($attributes, $identity->getAttributes());
		$this->assertEquals($attributes['email'], $identity->getEmail());
		$identity->setId(800);
		$this->assertEquals(800, $identity->getId());
		$this->assertFalse($identity->save());
		$identity->setId(null);
		$this->assertTrue($identity->save());
		$this->assertEquals(5, $identity->getId());
		$this->assertEquals($attributes, $identity->getAttributes());

		$identity = UserIdentity::find(array('username'=>'u1'));
		$this->assertEquals(5, $identity->getId());
		$this->assertEquals($attributes, $identity->getAttributes());

		$identity->setAttributes(array('username'=>null, 'password'=>null));
		$this->assertFalse($identity->save());
	}

	public function testRecord()
	{
		$fakeIdentity = new UserIdentity(null, null);
		$fakeIdentity->setId(999);
		$this->assertFalse($fakeIdentity->isDisabled());
		$this->assertFalse($fakeIdentity->isActive());

		$identity = new UserIdentity('neo','xxx');
		$this->assertFalse($identity->authenticate());
		$this->assertFalse($identity->getIsAuthenticated());

		$identity = new UserIdentity('tank','Test1233');
		$this->assertFalse($identity->authenticate());
		$this->assertFalse($identity->getIsAuthenticated());

		$identity = new UserIdentity('neo','Test1233');
		$this->assertTrue($identity->authenticate());
		$this->assertTrue($identity->getIsAuthenticated());
		$this->assertTrue($identity->isActive());
		$this->assertFalse($identity->isDisabled());

		$this->assertEquals('2011-11-11 12:34', $identity->getPasswordDate());
		$this->assertEquals('2011-11-11 12:34', $identity->getPasswordDate('Test1233'));
		$this->assertNull($identity->getPasswordDate('xxx'));

		$identity2 = UserIdentity::find(array('username'=>'neo'));
		$this->assertEquals($identity->getId(), $identity2->getId());

		$identity3 = UserIdentity::find(array('username'=>'tank'));
		$this->assertEquals(2, $identity3->getId());
		$this->assertFalse($identity3->isActive());
		$this->assertTrue($identity3->isDisabled());
	}

	public function testPasswordReset()
	{
		$fakeIdentity = new UserIdentity(null, null);
		$fakeIdentity->setId(999);
		$this->assertFalse($fakeIdentity->resetPassword('xx'));

		$identity = UserIdentity::find(array('username'=>'cat'));
		$this->assertEquals(4, $identity->getId());
		$this->assertFalse($identity->authenticate());
		$dateBefore = date('Y-m-d H:i:s');
		$this->assertTrue($identity->resetPassword('Test1234'));
		$dateAfter = date('Y-m-d H:i:s');
		$identity->password = 'Test1234';
		$this->assertTrue($identity->authenticate());
		$this->assertGreaterThanOrEqual($dateBefore, $identity->getPasswordDate());
		$this->assertLessThanOrEqual($dateAfter, $identity->getPasswordDate());
		$this->assertGreaterThanOrEqual($dateBefore, $identity->getPasswordDate('Test1234'));
		$this->assertLessThanOrEqual($dateAfter, $identity->getPasswordDate('Test1234'));
		$this->assertNull($identity->getPasswordDate('xx'));
	}

	public function testRemoteIdentity()
	{
		$identity = new UserIdentity('neo','Test1233');
		$this->assertFalse($identity->addRemoteIdentity('facebook', 'one'));
		$this->assertTrue($identity->authenticate());
		$this->assertTrue($identity->addRemoteIdentity('facebook', 'one'));
		$identity2 = UserIdentity::findByProvider('facebook', 'one');
		$this->assertEquals($identity->getId(), $identity2->getId());
	}

	public function testActivation()
	{
		$fakeIdentity = new UserIdentity(null, null);
		$fakeIdentity->setId(999);
		$this->assertFalse($fakeIdentity->getActivationKey());
		$this->assertEquals(UserIdentity::ERROR_AKEY_INVALID, $fakeIdentity->verifyActivationKey('xx'));

		$identity = UserIdentity::find(array('username'=>'neo'));
		$this->assertEquals(UserIdentity::ERROR_AKEY_INVALID, $identity->verifyActivationKey('xx'));
		$key = $identity->getActivationKey();
		$this->assertInternalType('string', $key);
		$this->assertEquals(UserIdentity::ERROR_AKEY_NONE, $identity->verifyActivationKey($key));
	}

	public function testVerifyEmail()
	{
		$fakeIdentity = new UserIdentity(null, null);
		$this->assertFalse($fakeIdentity->verifyEmail());
		$fakeIdentity->setId(999);
		$this->assertFalse($fakeIdentity->verifyEmail());

		$identity = UserIdentity::find(array('username'=>'neo'));
		$this->assertTrue($identity->verifyEmail());
	}
}
