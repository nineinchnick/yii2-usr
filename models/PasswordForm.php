<?php

/**
 * PasswordForm class.
 * PasswordForm is the data structure for keeping password form data. It is used by the 'register' and 'profile' actions of 'DefaultController'.
 */
class PasswordForm extends BasePasswordForm
{
	public $password;

	private $_identity;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		$rules = array_merge(array(
			array('password', 'filter', 'filter'=>'trim', 'except'=>'register'),
			array('password', 'required', 'except'=>'register'),
			array('password', 'authenticate', 'except'=>'register'),
		), parent::rules());

		return $rules;
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), array(
			'password' => Yii::t('UsrModule.usr','Current password'),
		));
	}

	public function getIdentity()
	{
		if($this->_identity===null) {
			if ($this->scenario === 'register')
				return $this->_identity;
			$userIdentityClass = $this->userIdentityClass;
			$this->_identity = $userIdentityClass::find(array('id'=>Yii::app()->user->getId()));
		}
		return $this->_identity;
	}

	public function setIdentity($identity)
	{
		$this->_identity = $identity;
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
		if (($identity=$this->getIdentity()) === null) {
			throw new CException('Current user has not been found in the database.');
		}
		$identity->password = $this->password;
		if(!$identity->authenticate()) {
			$this->addError('password',Yii::t('UsrModule.usr','Invalid password.'));
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
			$identity = $this->getIdentity();
		if (!$identity->resetPassword($this->newPassword)) {
			$this->addError('newPassword',Yii::t('UsrModule.usr','Failed to reset the password.'));
			return false;
		}
		return true;
	}
}
