<?php

namespace nineinchnick\usr\tests\unit;

use nineinchnick\usr\tests\TestCase as TestCase;

class BehaviorsTest extends TestCase
{
    public $identity;
    public $owner;

    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication($this->getParam('app'), '\yii\console\Application');

        $this->identity = $this->getMock('nineinchnick\usr\tests\User', ['getPasswordDate', 'getOneTimePasswordSecret', 'getOneTimePassword']);
        $this->identity->expects($this->any())->method('getPasswordDate')->will($this->returnValue('2013-10-10'));
        $this->identity->expects($this->any())->method('getOneTimePasswordSecret')->will($this->returnValue('ZCIDBJZMPVFXZIKA'));
        $this->identity->expects($this->any())->method('getOneTimePassword')->will($this->returnValue([null, 1]));

        $this->owner = $this->getMock('stdClass', ['on', 'getIdentity', 'hasErrors', 'getErrors', 'addError', 'rules', 'attributeLabels', 'attributes']);
        $this->owner->username = 'xx';
        $this->owner->expects($this->any())->method('on');
        $this->owner->expects($this->any())->method('getIdentity')->will($this->returnValue($this->identity));
        $this->owner->expects($this->any())->method('hasErrors')->will($this->returnValue(false));
        $this->owner->expects($this->any())->method('getErrors')->will($this->returnValue([]));
        $this->owner->expects($this->any())->method('addError');
        $this->owner->expects($this->any())->method('rules')->will($this->returnValue([['username', 'required']]));
        $this->owner->expects($this->any())->method('attributeLabels')->will($this->returnValue(['username' => 'label']));
        $this->owner->expects($this->any())->method('attributes')->will($this->returnValue(['username']));
    }

    public function testOTP()
    {
        $googleAuthenticator = new \Google\Authenticator\GoogleAuthenticator();
        $otp = \Yii::createObject([
            'class' => 'nineinchnick\usr\components\OneTimePasswordFormBehavior',
            'authenticator' => $googleAuthenticator,
            'mode' => \nineinchnick\usr\components\OneTimePasswordFormBehavior::OTP_COUNTER,
            'required' => false,
            'timeout' => 300,
        ]);
        $otp->attach($this->owner);

        $this->assertEquals('ZCIDBJZMPVFXZIKA', $otp->getOTP('secret'));

        $this->assertEquals(['oneTimePassword'], $otp->attributes());
        $this->assertEquals(['oneTimePassword'], $otp->attributes());
        $this->assertEquals(['oneTimePassword' => \Yii::t('usr', 'One Time Password')], $otp->attributeLabels());
        $rules = $otp->rules();

        $ruleOptions = ['on' => 'reset'];
        $otp->setRuleOptions($ruleOptions);
        $this->assertEquals($ruleOptions, $otp->getRuleOptions());

        $modifiedRules = $otp->rules();
        foreach ($modifiedRules as $rule) {
            foreach ($ruleOptions as $key => $value) {
                $this->assertEquals($value, $rule[$key]);
            }
        }

        $code = $otp->getNewCode();
        $this->assertInternalType('string', $code);
        $this->assertTrue(is_numeric($code));
        $this->assertEquals(6, strlen($code));

        $controller = $this->getMock('stdClass', ['sendEmail']);
        $controller->expects($this->once())->method('sendEmail')->with($this->equalTo($otp), $this->equalTo('oneTimePassword'));
        $otp->setController($controller);
        $this->assertFalse($otp->validOneTimePassword('one_time_password', []));
        $this->owner->one_time_password = '188172';
        $this->assertTrue($otp->validOneTimePassword('one_time_password', []));

        $cookie = $otp->createOTPCookie($this->owner->username, $otp->getOTP('secret'), $otp->getOTP('timeout'));
        $this->assertTrue($otp->isValidOTPCookie($cookie, $this->owner->username, $otp->getOTP('secret'), $otp->getOTP('timeout')));
        $cookie->value = 'xx';
        $this->assertFalse($otp->isValidOTPCookie($cookie, $this->owner->username, $otp->getOTP('secret'), $otp->getOTP('timeout')));
    }

    public function testCaptcha()
    {
        $captcha = \Yii::createObject([
            'class' => 'nineinchnick\usr\components\CaptchaFormBehavior',
        ]);
        $captcha->attach($this->owner);

        $this->assertEquals(['username'], $this->owner->attributes());
        $this->assertEquals(['username' => 'label'], $this->owner->attributeLabels());
        $this->assertEquals([['username', 'required']], $this->owner->rules());

        $this->assertEquals(['verifyCode'], $captcha->attributes());
        $this->assertEquals(['verifyCode' => \Yii::t('usr', 'Verification code')], $captcha->attributeLabels());
        $this->assertEquals([['verifyCode', 'captcha', 'captchaAction' => 'usr/default/captcha']], $captcha->rules());
    }

    public function testExpiredPassword()
    {
        $expired = \Yii::createObject([
            'class' => 'nineinchnick\usr\components\ExpiredPasswordBehavior',
        ]);
        $expired->attach($this->owner);

        $passwordSetDate = new \DateTime($this->identity->getPasswordDate());
        $today = new \DateTime();
        // this will force the pass to be expired
        $passwordTimeout = $today->diff($passwordSetDate)->days;
        $expired->setPasswordTimeout($passwordTimeout - 10);
        $this->assertEquals($passwordTimeout - 10, $expired->getPasswordTimeout());

        $this->assertFalse($expired->passwordHasNotExpired());

        $expired->setPasswordTimeout($passwordTimeout + 10);
        $this->assertTrue($expired->passwordHasNotExpired());
    }
}
