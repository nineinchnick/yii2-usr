<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'DefaultController'.
 */
class LoginForm extends BasePasswordForm
{
	public $username;
	public $password;
	public $rememberMe;

	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		$rules = array_merge([
			[['username', 'password'], 'filter', 'filter'=>'trim'],
			[['username', 'password'], 'required'],
			['rememberMe', 'boolean'],
			['password', 'authenticate'],
		], $this->rulesAddScenario(parent::rules(), 'reset'), $this->getBehaviorRules());

		return $rules;
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge($this->getBehaviorLabels(), parent::attributeLabels(), [
			'username'		=> Yii::t('usr','Username'),
			'password'		=> Yii::t('usr','Password'),
			'rememberMe'	=> Yii::t('usr','Remember me when logging in next time'),
		]);
	}

	public function getIdentity()
	{
		if($this->_identity===null) {
			$identityClass = Yii::$app->user->identityClass;
			if (($this->_identity = $identityClass::findByUsername($this->username)) === null)
				$this->_identity = false;
		}
		return $this->_identity;
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 */
	public function authenticate($attribute,$params)
	{
		if($this->hasErrors()) {
			return;
		}
		$identity = $this->getIdentity();
		if(!$identity || $identity->validatePassword($this->$attribute)) {
			$this->addError($attribute, Yii::t('usr','Invalid username or password.'));
			return false;
		}
		return true;
	}

	/**
	 * A wrapper for the passwordHasNotExpired method from ExpiredPasswordBehavior.
	 * @param $attribute string
	 * @param $params array
	 */
	public function passwordHasNotExpired($attribute, $params)
	{
		if (($behavior=$this->getBehavior('expiredPasswordBehavior')) !== null) {
			return $behavior->passwordHasNotExpired($attribute, $params);
		}
		return true;
	}

	/**
	 * A wrapper for the validOneTimePassword method from OneTimePasswordBehavior.
	 * @param $attribute string
	 * @param $params array
	 */
	public function validOneTimePassword($attribute, $params)
	{
		if (($behavior=$this->getBehavior('oneTimePasswordBehavior')) !== null) {
			return $behavior->validOneTimePassword($attribute, $params);
		}
		return true;
	}

	/**
	 * Resets user password using the new one given in the model.
	 * @return boolean whether password reset was successful
	 */
	public function resetPassword()
	{
		if($this->hasErrors()) {
			return;
		}
		$identity = $this->getIdentity();
		if (!$identity || !$identity->resetPassword($this->newPassword)) {
			$this->addError('newPassword',Yii::t('usr','Failed to reset the password.'));
			return false;
		}
		return true;
	}

	/**
	 * Logs in the user using the given username and password in the model.
	 * @param integer $duration For how long the user will be logged in without any activity, in seconds.
	 * @return boolean whether login is successful
	 */
	public function login($duration = 0)
	{
		$identity = $this->getIdentity();
		if (!$identity) {
			$authenticated = false;
		} elseif ($this->scenario === 'reset') {
			$authenticated = $identity->validatePassword($this->newPassword);
		} else {
			$authenticated = $identity->validatePassword($this->password);
		}
		if($authenticated) {
			return Yii::$app->user->login($identity, $this->rememberMe ? $duration : 0);
		}
		return false;
	}
}
