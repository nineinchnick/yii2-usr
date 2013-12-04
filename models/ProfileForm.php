<?php

namespace nineinchnick\usr\models;

use Yii;

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

	private $_user;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array_merge($this->getBehaviorRules(), [
			[['username', 'email', 'firstName', 'lastName'], 'filter', 'filter'=>'trim'],
			[['username', 'email', 'firstName', 'lastName'], 'default'],

			[['username', 'email'], 'required'],
			[['username', 'email'], 'uniqueIdentity'],
		]);
	}

	public function scenarios()
	{
		return [
			self::DEFAULT_SCENARIO => [],
			'register' => [],
		];
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge($this->getBehaviorLabels(), [
			'username'		=> Yii::t('usr','Username'),
			'email'			=> Yii::t('usr','Email'),
			'firstName'		=> Yii::t('usr','First name'),
			'lastName'		=> Yii::t('usr','Last name'),
		]);
	}

	public function getUser()
	{
		if($this->_user===null) {
			if ($this->scenario == 'register') {
				$userClass = Yii::$app->user->identityClass;
				$this->_user = new $userClass;
			} else {
				$this->_user = Yii::$app->user->getIdentity();
			}
			if ($this->_user !== null && !($this->_user instanceof IEditableIdentity)) {
				throw new CException(Yii::t('usr','The {class} class must implement the {interface} interface.',['class'=>get_class($this->_user),'interface'=>'IEditableIdentity']));
			}
		}
		return $this->_user;
	}

	public function uniqueIdentity($attribute,$params)
	{
		if($this->hasErrors()) {
			return;
		}
		$userClass = Yii::$app->user->identityClass;
		$existingIdentity = $userClass::find([$attribute => $this->$attribute]);
		if ($existingIdentity !== null && ($this->scenario == 'register' || (($identity=$this->getUser()) !== null && $existingIdentity->getId() != $identity->getId()))) {
			$this->addError($attribute,Yii::t('usr','{attribute} has already been used by another user.', ['attribute'=>$this->$attribute]));
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
		$identity = $this->getUser();

		return Yii::$app->user->login($identity,0);
	}

	/**
	 * Updates the identity with this models attributes and saves it.
	 */
	public function save()
	{
		$identity = $this->getUser();
		if ($identity === null)
			return false;

		$identity->setAttributes([
			'username'	=> $this->username,
			'email'		=> $this->email,
			'firstName'	=> $this->firstName,
			'lastName'	=> $this->lastName,
		]);
		if ($identity->save()) {
			$this->_user = $identity;
			return true;
		}
		return false;
	}
}
