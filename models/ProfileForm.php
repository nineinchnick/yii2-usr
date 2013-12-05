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

	private $_identity;

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
			self::DEFAULT_SCENARIO => $this->attributes(),
			'register' => $this->attributes(),
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

	public function getIdentity()
	{
		if($this->_identity===null) {
			if ($this->scenario == 'register') {
				$identityClass = Yii::$app->user->identityClass;
				$this->_identity = new $identityClass;
			} else {
				$this->_identity = Yii::$app->user->getIdentity();
			}
			if ($this->_identity !== null && !($this->_identity instanceof \nineinchnick\usr\components\EditableIdentityInterface)) {
				throw new \yii\base\Exception(Yii::t('usr','The {class} class must implement the {interface} interface.', ['class'=>get_class($this->_identity),'interface'=>'\nineinchnick\usr\components\EditableIdentityInterface']));
			}
		}
		return $this->_identity;
	}

	public function uniqueIdentity($attribute,$params)
	{
		if($this->hasErrors()) {
			return;
		}
		$identityClass = Yii::$app->user->identityClass;
		$existingIdentity = $identityClass::find([$attribute => $this->$attribute]);
		if ($existingIdentity !== null && ($this->scenario == 'register' || (($identity=$this->getIdentity()) !== null && $existingIdentity->getId() != $identity->getId()))) {
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
		$identity = $this->getIdentity();

		return Yii::$app->user->login($identity,0);
	}

	/**
	 * Updates the identity with this models attributes and saves it.
	 */
	public function save()
	{
		$identity = $this->getIdentity();
		if ($identity === null)
			return false;

		$identity->setIdentityAttributes([
			'username'	=> $this->username,
			'email'		=> $this->email,
			'firstName'	=> $this->firstName,
			'lastName'	=> $this->lastName,
		]);
		if ($identity->saveIdentity()) {
			$this->_identity = $identity;
			return true;
		}
		return false;
	}
}
