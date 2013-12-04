<?php

Yii::import('vendors.nineinchnick.yii-usr.tests.User');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserIdentity');
Yii::import('vendors.nineinchnick.yii-usr.components.FormModelBehavior');

class BehaviorsTest extends CTestCase
{
	public $identity;
	public $owner;

	protected function setUp()
	{
		$this->identity = $this->getMock('UserIdentity', array('getPasswordDate', 'getOneTimePasswordSecret', 'getOneTimePassword'), array(null, null));
		$this->identity->expects($this->any())->method('getPasswordDate')->will($this->returnValue('2013-10-10'));
		$this->identity->expects($this->any())->method('getOneTimePasswordSecret')->will($this->returnValue('ZCIDBJZMPVFXZIKA'));
		$this->identity->expects($this->any())->method('getOneTimePassword')->will($this->returnValue(array(null, 1)));

		$this->owner = $this->getMock('stdClass', array('getIdentity', 'hasErrors', 'getErrors', 'addError', 'rules', 'attributeLabels', 'attributeNames'));
		$this->owner->username = 'xx';
		$this->owner->expects($this->any())->method('getIdentity')->will($this->returnValue($this->identity));
		$this->owner->expects($this->any())->method('hasErrors')->will($this->returnValue(false));
		$this->owner->expects($this->any())->method('getErrors')->will($this->returnValue(array()));
		$this->owner->expects($this->any())->method('addError');
		$this->owner->expects($this->any())->method('rules')->will($this->returnValue(array(array('username', 'required'))));
		$this->owner->expects($this->any())->method('attributeLabels')->will($this->returnValue(array('username'=>'label')));
		$this->owner->expects($this->any())->method('attributeNames')->will($this->returnValue(array('username')));
	}

	public function testOTP()
	{
		Yii::import('vendors.nineinchnick.yii-usr.components.OneTimePasswordFormBehavior');

		require dirname(__FILE__) . '/../../extensions/GoogleAuthenticator.php/lib/GoogleAuthenticator.php';
		$googleAuthenticator = new GoogleAuthenticator;
		$otp = Yii::createComponent(array(
			'class' => 'OneTimePasswordFormBehavior',
			'oneTimePasswordConfig' => array(
				'authenticator' => $googleAuthenticator,
				'mode' => UsrModule::OTP_COUNTER,
				'required' => false,
				'timeout' => 300,
			),
		));
		$otp->setEnabled(true);
		$otp->attach($this->owner);

		$this->assertEquals('ZCIDBJZMPVFXZIKA', $otp->getOTP('secret'));

		$this->assertEquals(array('oneTimePassword'), $otp->attributeNames());
		$this->assertEquals(array('oneTimePassword'), $otp->attributeNames());
		$this->assertEquals(array('oneTimePassword' => Yii::t('UsrModule.usr','One Time Password')), $otp->attributeLabels());
		$rules = $otp->rules();

		$ruleOptions = array('on'=>'reset');
		$otp->setRuleOptions($ruleOptions);
		$this->assertEquals($ruleOptions, $otp->getRuleOptions());

		$modifiedRules = $otp->rules();
		foreach($modifiedRules as $rule) {
			foreach($ruleOptions as $key=>$value) {
				$this->assertEquals($value, $rule[$key]);
			}
		}

		$code = $otp->getNewCode();
		$this->assertInternalType('string', $code);
		$this->assertTrue(is_numeric($code));
		$this->assertEquals(6,strlen($code));

		$controller = $this->getMock('stdClass', array('sendEmail'));
		$controller->expects($this->once())->method('sendEmail')->with($this->equalTo($otp), $this->equalTo('oneTimePassword'));
		$otp->setController($controller);
		$this->assertFalse($otp->validOneTimePassword('one_time_password', array()));
		$this->owner->one_time_password = '188172';
		$this->assertTrue($otp->validOneTimePassword('one_time_password', array()));

		$cookie = $otp->createOTPCookie($this->owner->username, $otp->getOTP('secret'), $otp->getOTP('timeout'));
		$this->assertTrue($otp->isValidOTPCookie($cookie, $this->owner->username, $otp->getOTP('secret'), $otp->getOTP('timeout')));
		$cookie->value = 'xx';
		$this->assertFalse($otp->isValidOTPCookie($cookie, $this->owner->username, $otp->getOTP('secret'), $otp->getOTP('timeout')));
	}

	public function testCaptcha()
	{
		Yii::import('vendors.nineinchnick.yii-usr.components.CaptchaFormBehavior');
		$captcha = Yii::createComponent(array(
			'class' => 'CaptchaFormBehavior',
		));
		$captcha->setEnabled(true);
		$captcha->attach($this->owner);

		$this->assertEquals(array('username'), $this->owner->attributeNames());
		$this->assertEquals(array('username' => 'label'), $this->owner->attributeLabels());
		$this->assertEquals(array(array('username', 'required')), $this->owner->rules());

		$this->assertEquals(array('verifyCode'), $captcha->attributeNames());
		$this->assertEquals(array('verifyCode' => Yii::t('UsrModule.usr','Verification code')), $captcha->attributeLabels());
		$this->assertEquals(array(array('verifyCode', 'captcha')), $captcha->rules());
	}

	public function testExpiredPassword()
	{
		Yii::import('vendors.nineinchnick.yii-usr.components.ExpiredPasswordBehavior');
		$expired = Yii::createComponent(array(
			'class' => 'ExpiredPasswordBehavior',
		));
		$expired->setEnabled(true);
		$expired->attach($this->owner);

		$passwordSetDate = new DateTime($this->identity->getPasswordDate());
		$today = new DateTime();
		// this will force the pass to be expired
		$passwordTimeout = $today->diff($passwordSetDate)->days;
		$expired->setPasswordTimeout($passwordTimeout - 10);
		$this->assertEquals($passwordTimeout - 10, $expired->getPasswordTimeout());

		$this->assertFalse($expired->passwordHasNotExpired());

		$expired->setPasswordTimeout($passwordTimeout + 10);
		$this->assertTrue($expired->passwordHasNotExpired());
	}
}
