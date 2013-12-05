<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * PasswordForm class.
 * PasswordForm is the data structure for keeping password form data. It is used by the 'register' and 'profile' actions of 'DefaultController'.
 */
class PasswordForm extends BasePasswordForm
{
	public $password;

	private $_user;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		$rules = array_merge([
			['password', 'filter', 'filter'=>'trim', 'except'=>'register'],
			['password', 'required', 'except'=>'register'],
			['password', 'authenticate', 'except'=>'register'],
		], parent::rules());

		return $rules;
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), [
			'password' => Yii::t('usr','Current password'),
		]);
	}

	public function getUser()
	{
		if($this->_user===null) {
			if ($this->scenario === 'register')
				return $this->_user;
			$this->_user = Yii::$app->user->getIdentity();
		}
		return $this->_user;
	}

	public function setIdentity($identity)
	{
		$this->_user = $identity;
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
		if (($identity=$this->getUser()) === null) {
			throw new \yii\base\Exception('Current user has not been found in the database.');
		}
		if(!$identity->validatePassword($this->$attribute)) {
			$this->addError($attribute,Yii::t('usr','Invalid password.'));
			return false;
		}
		return true;
	}

	/**
	 * Resets user password using the new one given in the model.
	 * @return boolean whether password reset was successful
	 */
	public function resetPassword($identity=null)
	{
		if($this->hasErrors()) {
			return;
		}
		if ($identity === null)
			$identity = $this->getUser();
		if (!$identity->resetPassword($this->newPassword)) {
			$this->addError('newPassword',Yii::t('usr','Failed to reset the password.'));
			return false;
		}
		return true;
	}
}
