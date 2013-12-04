<?php

/**
 * OneTimePasswordForm class.
 * OneTimePasswordForm is the data structure for keeping
 * one time password secret form data. It is used by the 'toggleOneTimePassword' action of 'DefaultController'.
 */
class OneTimePasswordForm extends CFormModel
{
	public $oneTimePassword;

	private $_user;

	private $_mode;
	private $_authenticator;
	private $_secret;

	private $_previousCounter;
	private $_previousCode;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return [
			['oneTimePassword', 'filter', 'filter'=>'trim'],
			['oneTimePassword', 'default'],
			['oneTimePassword', 'required'],
			['oneTimePassword', 'validOneTimePassword'],
		];
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return [
			'oneTimePassword' => Yii::t('usr','One Time Password'),
		];
	}

	public function setMode($mode)
	{
		$this->_mode = $mode;
		return $this;
	}

	public function setAuthenticator($authenticator)
	{
		$this->_authenticator = $authenticator;
		return $this;
	}

	public function setSecret($secret)
	{
		$this->_secret = $secret;
		return $this;
	}

	public function getPreviousCode()
	{
		if ($this->_previousCode === null) {
			list($this->_previousCode, $this->_previousCounter) = $this->getUser()->getOneTimePassword();
		}
		return $this->_previousCode;
	}

	public function getPreviousCounter()
	{
		if ($this->_previousCounter === null) {
			list($this->_previousCode, $this->_previousCounter) = $this->getUser()->getOneTimePassword();
		}
		return $this->_previousCounter;
	}

	public function getNewCode()
	{
		return $this->_authenticator->getCode($this->_secret, $this->_mode == nineinchnick\usr\Module::OTP_TIME ? null : $this->getPreviousCounter());
	}

    public function getUrl($user, $hostname, $secret) {
        $url =  "otpauth://totp/$user@$hostname%3Fsecret%3D$secret";
        $encoder = "https://www.google.com/chart?chs=200x200&chld=M|0&cht=qr&chl=";
        return $encoder.$url;
	}

	public function getUser()
	{
		if($this->_user===null) {
			$this->_user = Yii::$app->user->getIdentity();
			if (!($this->_user instanceof IOneTimePasswordIdentity)) {
				throw new CException(Yii::t('usr','The {class} class must implement the {interface} interface.',['class'=>get_class($this->_user),'interface'=>'IOneTimePasswordIdentity']));
			}
		}
		return $this->_user;
	}

	public function validOneTimePassword($attribute,$params)
	{
		if ($this->_mode === nineinchnick\usr\Module::OTP_TIME) {
			$valid = $this->_authenticator->checkCode($this->_secret, $this->$attribute);
		} elseif ($this->_mode === nineinchnick\usr\Module::OTP_COUNTER) {
			$valid = $this->_authenticator->getCode($this->_secret, $this->getPreviousCounter()) == $this->$attribute;
		} else {
			$valid = false;
		}
		if (!$valid) {
			$this->addError($attribute,Yii::t('usr','Entered code is invalid.'));
			return false;
		}
		if ($this->$attribute == $this->getPreviousCode()) {
			if ($this->_mode === nineinchnick\usr\Module::OTP_TIME) {
				$message = Yii::t('usr','Please wait until next code will be generated.');
			} elseif ($this->_mode === nineinchnick\usr\Module::OTP_COUNTER) {
				$message = Yii::t('usr','Please log in again to request a new code.');
			}
			$this->addError($attribute,Yii::t('usr','Entered code has already been used.').' '.$message);
			$this->scenario = 'verifyOTP';
			return false;
		}
		return true;
	}
}
