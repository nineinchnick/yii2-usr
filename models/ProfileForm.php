<?php

/**
 * ProfileForm class.
 * ProfileForm is the data structure for keeping
 * password recovery form data. It is used by the 'recovery' action of 'DefaultController'.
 */
class ProfileForm extends BaseUsrForm
{
	public $username;
	public $email;
	public $firstName;
	public $lastName;

	private $_identity;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array_merge($this->getBehaviorRules(), array(
			array('username, email, firstName, lastName', 'filter', 'filter'=>'trim'),
			array('username, email, firstName, lastName', 'default', 'setOnEmpty'=>true, 'value' => null),

			array('username, email', 'required'),
			array('username, email', 'uniqueIdentity'),
		));
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge($this->getBehaviorLabels(), array(
			'username'		=> Yii::t('UsrModule.usr','Username'),
			'email'			=> Yii::t('UsrModule.usr','Email'),
			'firstName'		=> Yii::t('UsrModule.usr','First name'),
			'lastName'		=> Yii::t('UsrModule.usr','Last name'),
		));
	}

	public function getIdentity()
	{
		if($this->_identity===null) {
			$userIdentityClass = $this->userIdentityClass;
			if ($this->scenario == 'register') {
				$this->_identity = new $userIdentityClass(null, null);
			} else {
				$this->_identity = $userIdentityClass::find(array('id'=>Yii::app()->user->getId()));
			}
			if ($this->_identity !== null && !($this->_identity instanceof IEditableIdentity)) {
				throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>get_class($this->_identity),'{interface}'=>'IEditableIdentity')));
			}
		}
		return $this->_identity;
	}

	public function uniqueIdentity($attribute,$params)
	{
		if($this->hasErrors()) {
			return;
		}
		$userIdentityClass = $this->userIdentityClass;
		$existingIdentity = $userIdentityClass::find(array($attribute => $this->$attribute));
		if ($existingIdentity !== null && ($this->scenario == 'register' || (($identity=$this->getIdentity()) !== null && $existingIdentity->getId() != $identity->getId()))) {
			$this->addError($attribute,Yii::t('UsrModule.usr','{attribute} has already been used by another user.', array('{attribute}'=>$this->$attribute)));
			return false;
		}
		return true;
	}

	/**
	 * Logs in the user using the given username.
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		$identity = $this->getIdentity();

		return Yii::app()->user->login($identity,0);
	}

	/**
	 * Updates the identity with this models attributes and saves it.
	 */
	public function save()
	{
		$identity = $this->getIdentity();
		if ($identity === null)
			return false;

		$identity->setAttributes(array(
			'username'	=> $this->username,
			'email'		=> $this->email,
			'firstName'	=> $this->firstName,
			'lastName'	=> $this->lastName,
		));
		if ($identity->save()) {
			$this->_identity = $identity;
			return true;
		}
		return false;
	}
}
