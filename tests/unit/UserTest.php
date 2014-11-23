<?php

namespace nineinchnick\usr\tests\unit;

use nineinchnick\usr\tests\DatabaseTestCase as DatabaseTestCase;
use nineinchnick\usr\tests\User;

class UserTest extends DatabaseTestCase
{
    public $fixtures = [
        'users' => 'User',
        'user_used_passwords' => 'UserUsedPassword',
    ];

    /**
     */
    public function testGetSet()
    {
        $identity = new User();
        $attributes = [
            'id' => null,
            'username' => 'u1',
            'password' => 'x',
            'email' => 'e1',
            'firstname' => 'f1',
            'lastname' => 'l1',
            'activation_key' => null,
            'created_on' => null,
            'updated_on' => null,
            'last_visit_on' => null,
            'password_set_on' => null,
            'email_verified' => 0,
            'is_active' => '1',
            'is_disabled' => '1',
            'one_time_password_secret' => null,
            'one_time_password_code' => null,
            'one_time_password_counter' => 1,
        ];
        $identity->setAttributes($attributes, false);
        $this->assertEquals($attributes, $identity->getAttributes());
        $this->assertEquals($attributes['email'], $identity->getEmail());
        $identity->id = 800;
        $this->assertEquals(800, $identity->getId());
        //$this->assertFalse($identity->save());
        $identity->id = null;
        $this->assertTrue($identity->save());
        $this->assertEquals(5, $identity->getId());
        $attributes['id'] = '5';
        $savedAttributes = $identity->getAttributes();
        $savedAttributes['created_on'] = null;
        $this->assertEquals($attributes, $savedAttributes);

        $identity = User::find(['username' => 'u1']);
        $this->assertEquals(5, $identity->getId());
        $savedAttributes = $identity->getAttributes();
        $savedAttributes['created_on'] = null;
        $this->assertEquals($attributes, $savedAttributes);

        $identity->setAttributes(['username' => null, 'password' => null]);
        $this->assertFalse($identity->save());
    }

    public function testRecord()
    {
        $fake = new User();
        $fake->id = 999;
        $this->assertFalse($fake->isDisabled());
        $this->assertFalse($fake->isActive());

        $identity = new User();
        $identity->setAttributes(['username' => 'neo', 'password' => 'xxx'], false);
        $this->assertFalse($identity->authenticate('xxx'));

        $identity = new User();
        $identity->setAttributes(['username' => 'tank', 'password' => 'Test1233'], false);
        $this->assertFalse($identity->authenticate('Test1233'));

        $identity = User::find(['username' => 'neo']);
        $this->assertTrue($identity->authenticate('Test1233'));
        $this->assertTrue($identity->isActive());
        $this->assertFalse($identity->isDisabled());

        $this->assertEquals('2011-11-11 12:34', $identity->getPasswordDate());
        $this->assertEquals('2011-11-11 12:34', $identity->getPasswordDate('Test1233'));
        $this->assertNull($identity->getPasswordDate('xxx'));

        $identity2 = User::find(['username' => 'neo']);
        $this->assertEquals($identity->getId(), $identity2->getId());

        $identity3 = User::find(['username' => 'tank']);
        $this->assertEquals(2, $identity3->getId());
        $this->assertFalse($identity3->isActive());
        $this->assertTrue($identity3->isDisabled());
    }

    public function testPasswordReset()
    {
        $fake = new User();
        $fake->id = 999;
        $this->assertFalse($fake->resetPassword('xx'));

        $identity = User::find(['username' => 'cat']);
        $this->assertEquals(4, $identity->getId());
        $this->assertFalse($identity->authenticate(' '));
        $dateBefore = date('Y-m-d H:i:s');
        $this->assertTrue($identity->resetPassword('Test1234'));
        $dateAfter = date('Y-m-d H:i:s');
        $this->assertTrue($identity->authenticate('Test1234'));
        $this->assertGreaterThanOrEqual($dateBefore, $identity->getPasswordDate());
        $this->assertLessThanOrEqual($dateAfter, $identity->getPasswordDate());
        $this->assertGreaterThanOrEqual($dateBefore, $identity->getPasswordDate('Test1234'));
        $this->assertLessThanOrEqual($dateAfter, $identity->getPasswordDate('Test1234'));
        $this->assertNull($identity->getPasswordDate('xx'));
    }

    public function testRemote()
    {
        $identity = User::find(['username' => 'neo']);
        //$this->assertFalse($identity->addRemoteIdentity('facebook', 'one'));
        $this->assertTrue($identity->authenticate('Test1233'));
        $this->assertTrue($identity->addRemoteIdentity('facebook', 'one'));
        $identity2 = User::findByProvider('facebook', 'one');
        $this->assertEquals($identity->getId(), $identity2->getId());
    }

    public function testActivation()
    {
        $identity = User::find(['username' => 'neo']);
        $this->assertEquals(User::ERROR_AKEY_INVALID, $identity->verifyActivationKey('xx'));
        $key = $identity->getActivationKey();
        $this->assertInternalType('string', $key);
        $this->assertEquals(User::ERROR_AKEY_NONE, $identity->verifyActivationKey($key));
    }

    public function testVerifyEmail()
    {
        $fake = new User();
        $fake->setAttributes(['id' => 999, 'username' => 'fake2', 'password' => 'Test1233', 'email' => 'fake2@matrix.com'], false);
        $this->assertTrue($fake->verifyEmail());

        $identity = User::find(['username' => 'neo']);
        $this->assertTrue($identity->verifyEmail());
    }
}
