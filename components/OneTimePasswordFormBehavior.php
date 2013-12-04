<?php
/**
 * OneTimePasswordFormBehavior class file.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */

/**
 * OneTimePasswordFormBehavior adds one time password validation to a login form model component.
 *
 * @property CFormModel $owner The owner model that this behavior is attached to.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class OneTimePasswordFormBehavior extends FormModelBehavior
{
	public $oneTimePassword;

	private $_oneTimePasswordConfig = array(
		'authenticator' => null,
		'mode' => null,
		'required' => null,
		'timeout' => null,
		'secret' => null,
		'previousCode' => null,
		'previousCounter' => null,
	);

	private $_controller;

	public function events() {
		return array_merge(parent::events(), array(
			'onAfterValidate'=>'afterValidate',
		));
	}

	public function rules()
	{
		$rules = array(
			array('oneTimePassword', 'filter', 'filter'=>'trim', 'on'=>'verifyOTP'),
			array('oneTimePassword', 'default', 'setOnEmpty'=>true, 'value' => null, 'on'=>'verifyOTP'),
			array('oneTimePassword', 'required', 'on'=>'verifyOTP'),
			array('oneTimePassword', 'validOneTimePassword', 'except'=>'hybridauth'),
		);
		return $this->applyRuleOptions($rules);
	}

	public function attributeLabels()
	{
		return array(
			'oneTimePassword' => Yii::t('UsrModule.usr','One Time Password'),
		);
	}

	public function getController()
	{
		return $this->_controller;
	}

	public function setController($value)
	{
		$this->_controller = $value;
	}

	public function getOneTimePasswordConfig()
	{
		return $this->_oneTimePasswordConfig;
	}

	public function setOneTimePasswordConfig(array $config)
	{
		foreach($config as $key => $value) {
			if ($this->_oneTimePasswordConfig[$key] === null)
				$this->_oneTimePasswordConfig[$key] = $value;
		}
		return $this;
	}

	protected function loadOneTimePasswordConfig()
	{
		$identity = $this->owner->getIdentity();
		if (!($identity instanceof IOneTimePasswordIdentity))
			throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>get_class($identity),'{interface}'=>'IOneTimePasswordIdentity')));
		list($previousCode, $previousCounter) = $identity->getOneTimePassword();
		$this->setOneTimePasswordConfig(array(
			'secret' => $identity->getOneTimePasswordSecret(),
			'previousCode' => $previousCode,
			'previousCounter' => $previousCounter,
		));
		return $this;
	}

	public function getOTP($key)
	{
		if ($this->_oneTimePasswordConfig[$key] === null) {
			$this->loadOneTimePasswordConfig();
		}
		return $this->_oneTimePasswordConfig[$key];
	}

	public function getNewCode()
	{
		$this->loadOneTimePasswordConfig();
		// extracts: $authenticator, $mode, $required, $timeout, $secret, $previousCode, $previousCounter
		extract($this->_oneTimePasswordConfig);
		return $authenticator->getCode($secret, $mode == UsrModule::OTP_TIME ? null : $previousCounter);
	}

	public function validOneTimePassword($attribute,$params)
	{
		if($this->owner->hasErrors()) {
			return;
		}
		$this->loadOneTimePasswordConfig();
		// extracts: $authenticator, $mode, $required, $timeout, $secret, $previousCode, $previousCounter
		extract($this->_oneTimePasswordConfig);

		if (($mode !== UsrModule::OTP_TIME && $mode !== UsrModule::OTP_COUNTER) || (!$required && $secret === null)) {
			return true;
		}
		if ($required && $secret === null) {
			// generate and save a new secret only if required to do so, in other cases user must verify that the secret works
			$secret = $this->_oneTimePasswordConfig['secret'] = $authenticator->generateSecret();
			$this->owner->getIdentity()->setOneTimePasswordSecret($secret);
		}

		if ($this->isValidOTPCookie(Yii::app()->request->cookies->itemAt(UsrModule::OTP_COOKIE), $this->owner->username, $secret, $timeout)) {
			return true;
		}
		if (empty($this->owner->$attribute)) {
			$this->owner->addError($attribute,Yii::t('UsrModule.usr','Enter a valid one time password.'));
			$this->owner->scenario = 'verifyOTP';
			if ($mode === UsrModule::OTP_COUNTER) {
				$this->_controller->sendEmail($this, 'oneTimePassword');
			}
			if (YII_DEBUG) {
				$this->oneTimePassword = $authenticator->getCode($secret, $mode === UsrModule::OTP_TIME ? null : $previousCounter);
			}
			return false;
		}
		if ($mode === UsrModule::OTP_TIME) {
			$valid = $authenticator->checkCode($secret, $this->owner->$attribute);
		} elseif ($mode === UsrModule::OTP_COUNTER) {
			$valid = $authenticator->getCode($secret, $previousCounter) == $this->owner->$attribute;
		} else {
			$valid = false;
		}
		if (!$valid) {
			$this->owner->addError($attribute,Yii::t('UsrModule.usr','Entered code is invalid.'));
			$this->owner->scenario = 'verifyOTP';
			return false;
		}
		if ($this->owner->$attribute == $previousCode) {
			if ($mode === UsrModule::OTP_TIME) {
				$message = Yii::t('UsrModule.usr','Please wait until next code will be generated.');
			} elseif ($mode === UsrModule::OTP_COUNTER) {
				$message = Yii::t('UsrModule.usr','Please log in again to request a new code.');
			}
			$this->owner->addError($attribute,Yii::t('UsrModule.usr','Entered code has already been used.').' '.$message);
			$this->owner->scenario = 'verifyOTP';
			return false;
		}
		$this->owner->getIdentity()->setOneTimePassword($this->owner->$attribute, $mode === UsrModule::OTP_TIME ? floor(time() / 30) : $previousCounter + 1);
		return true;
	}

	protected function afterValidate($event)
	{
		if ($this->owner->scenario === 'hybridauth')
			return;

		// extracts: $authenticator, $mode, $required, $timeout, $secret, $previousCode, $previousCounter
		extract($this->_oneTimePasswordConfig);

		$cookie = $this->createOTPCookie($this->owner->username, $secret, $timeout);
		Yii::app()->request->cookies->add($cookie->name,$cookie);
	}

	public function createOTPCookie($username, $secret, $timeout, $time = null) {
		if ($time === null)
			$time = time();
		$cookie=new CHttpCookie(UsrModule::OTP_COOKIE,'');
		$cookie->expire=time() + ($timeout <= 0 ? 10*365*24*3600 : $timeout);
		$cookie->httpOnly=true;
		$data=array('username'=>$username, 'time'=>$time, 'timeout'=>$timeout);
		$cookie->value=$time.':'.Yii::app()->getSecurityManager()->computeHMAC(serialize($data), $secret);
		return $cookie;
	}

	public function isValidOTPCookie($cookie, $username, $secret, $timeout, $time = null) {
		if ($time === null)
			$time = time();

		if(!$cookie || empty($cookie->value) || !is_string($cookie->value)) {
			return false;
		}
		$parts = explode(":",$cookie->value,2);
		if (count($parts)!=2) {
			return false;
		}
		list($creationTime,$hash) = $parts;
		$data=array('username'=>$username, 'time'=>(int)$creationTime, 'timeout'=>$timeout);
		$validHash = Yii::app()->getSecurityManager()->computeHMAC(serialize($data), $secret);
		return ($timeout <= 0 || $creationTime + $timeout >= $time) && $hash === $validHash;
	}
}
