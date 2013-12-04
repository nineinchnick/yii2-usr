<?php

/**
 * RecoveryForm class.
 * RecoveryForm is the data structure for keeping
 * password recovery form data. It is used by the 'recovery' action of 'DefaultController'.
 */
class RecoveryForm extends BasePasswordForm
{
	public $username;
	public $email;
	public $activationKey;

	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules() {
		$rules = array_merge($this->getBehaviorRules(), array(
			array('username, email', 'filter', 'filter'=>'trim'),
			array('username, email', 'default', 'setOnEmpty'=>true, 'value' => null),
			array('username, email', 'existingIdentity'),

			array('activationKey', 'filter', 'filter'=>'trim', 'on'=>'reset,verify'),
			array('activationKey', 'default', 'setOnEmpty'=>true, 'value' => null, 'on'=>'reset,verify'),
			array('activationKey', 'required', 'on'=>'reset,verify'),
			array('activationKey', 'validActivationKey', 'on'=>'reset,verify'),
		), $this->rulesAddScenario(parent::rules(), 'reset'));

		return $rules;
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels() {
		return array_merge($this->getBehaviorLabels(), parent::attributeLabels(), array(
			'username'		=> Yii::t('UsrModule.usr','Username'),
			'email'			=> Yii::t('UsrModule.usr','Email'),
			'activationKey'	=> Yii::t('UsrModule.usr','Activation Key'),
		));
	}

	public function getIdentity() {
		if($this->_identity===null) {
			// generate a fake object just to check if it implements a correct interface
			$userIdentityClass = $this->userIdentityClass;
			$fakeIdentity = new $userIdentityClass(null, null);
			if (!($fakeIdentity instanceof IActivatedIdentity)) {
				throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>$userIdentityClass, '{interface}'=>'IActivatedIdentity')));
			}
			$attributes = array();
			if ($this->username !== null) $attributes['username'] = $this->username;
			if ($this->email !== null) $attributes['email'] = $this->email;
			if (!empty($attributes))
				$this->_identity=$userIdentityClass::find($attributes);
		}
		return $this->_identity;
	}

	public function existingIdentity($attribute,$params) {
		if($this->hasErrors()) {
			return;
		}
		$identity = $this->getIdentity();
		if ($identity === null) {
			if ($this->username !== null) {
				$this->addError('username',Yii::t('UsrModule.usr','No user found matching this username.'));
			} elseif ($this->email !== null) {
				$this->addError('email',Yii::t('UsrModule.usr','No user found matching this email address.'));
			} else {
				$this->addError('username',Yii::t('UsrModule.usr','Please specify username or email.'));
			}
			return false;
		}
		return true;
	}

	/**
	 * Validates the activation key.
	 */
	public function validActivationKey($attribute,$params) {
		if($this->hasErrors()) {
			return;
		}
		if (($identity = $this->getIdentity()) === null)
			return false;

		$errorCode = $identity->verifyActivationKey($this->activationKey);
		switch($errorCode) {
			default:
			case $identity::ERROR_AKEY_INVALID:
				$this->addError('activationKey',Yii::t('UsrModule.usr','Activation key is invalid.'));
				return false;
			case $identity::ERROR_AKEY_TOO_OLD:
				$this->addError('activationKey',Yii::t('UsrModule.usr','Activation key is too old.'));
				return false;
			case $identity::ERROR_AKEY_NONE:
				return true;
		}
		return true;
	}

	/**
	 * Resets user password using the new one given in the model.
	 * @return boolean whether password reset was successful
	 */
	public function resetPassword() {
		$identity = $this->getIdentity();
		return $identity->resetPassword($this->newPassword);
	}

	/**
	 * Logs in the user using the given username and new password.
	 * @return boolean whether login is successful
	 */
	public function login() {
		$identity = $this->getIdentity();

		$identity->password = $this->newPassword;
		$identity->authenticate();
		if($identity->getIsAuthenticated()) {
			return Yii::app()->user->login($identity,0);
		}
		return false;
	}
}
