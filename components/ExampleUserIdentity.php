<?php

Yii::import('usr.components.*');

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
abstract class ExampleUserIdentity extends CUserIdentity implements IPasswordHistoryIdentity,IActivatedIdentity,IEditableIdentity,IHybridauthIdentity,IOneTimePasswordIdentity
{
	public $email = null;
	public $firstName = null;
	public $lastName = null;
	private $_id = null;

	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$record=User::model()->findByAttributes(array('username'=>$this->username));
		if ($record!==null && $record->is_active && !$record->is_disabled && $record->verifyPassword($this->password)) {
			$this->_id = $record->id;
			$this->email = $record->email;
			$this->errorCode=self::ERROR_NONE;
			$record->saveAttributes(array('last_visit_on'=>date('Y-m-d H:i:s')));
		} else {
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		}
		return $this->getIsAuthenticated();
	}
	
	public function setId($id)
	{
		$this->_id = $id;
	}
	
	public function getId()
	{
		return $this->_id;
	}

	public function getPasswordDate($password = null)
	{
		if ($this->_id === null || ($record=User::model()->findByPk($this->_id)) === null)
			return null;

		if ($password === null) {
			return $record->password_set_on;
		} else {
			foreach($record->userUsedPasswords as $usedPassword) {
				if ($usedPassword->verifyPassword($password))
					return $usedPassword->set_on;
			}
		}
		return null;
	}

	public function resetPassword($password)
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			$hashedPassword = User::hashPassword($password);
			$usedPassword = new UserUsedPassword;
			$usedPassword->setAttributes(array(
				'user_id'=>$this->_id,
				'password'=>$hashedPassword,
				'set_on'=>date('Y-m-d H:i:s'),
			), false);
			return $usedPassword->save() && $record->saveAttributes(array(
				'password'=>$hashedPassword,
				'password_set_on'=>date('Y-m-d H:i:s'),
			));
		}
		return false;
	}

	protected static function createFromUser(User $user)
	{
		$identity = new UserIdentity($user->username, null);
		$identity->id = $user->id;
		$identity->username = $user->username;
		$identity->email = $user->email;
		$identity->firstName = $user->firstname;
		$identity->lastName = $user->lastname;
		return $identity;
	}

	public static function find(array $attributes)
	{
		$record = User::model()->findByAttributes($attributes);
		return $record === null ? null : self::createFromUser($record);
	}

	public static function findByProvider($provider, $identifier)
	{
		$criteria = new CDbCriteria;
		$criteria->with['userRemoteIdentities'] = array('alias'=>'ur');
		$criteria->compare('ur.provider',$provider);
		$criteria->compare('ur.identifier',$identifier);
		$record = User::model()->find($criteria);
		return $record === null ? null : self::createFromUser($record);
	}

	public function addRemoteIdentity($provider, $identifier)
	{
		if ($this->_id===null)
			return false;
		$model = new UserRemoteIdentity;
		$model->setAttributes(array(
			'user_id' => $this->_id,
			'provider' => $provider,
			'identifier' => $identifier,
		), false);
		return $model->save();
	}

	public function getActivationKey()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			$activationKey = md5(time().mt_rand().$record->username);
			if (!$record->saveAttributes(array('activation_key' => $activationKey))) {
				return false;
			}
			return $activationKey;
		}
		return false;
	}

	public function verifyActivationKey($activationKey)
	{
		if ($this->_id===null)
			return self::ERROR_AKEY_INVALID;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->activation_key === $activationKey ? self::ERROR_AKEY_NONE : self::ERROR_AKEY_INVALID;
		}
		return self::ERROR_AKEY_INVALID;
	}

	public function isActive()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return (bool)$record->is_active;
		}
		return false;
	}

	public function isDisabled()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return (bool)$record->is_disabled;
		}
		return false;
	}

	public function verifyEmail()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			if (!$record->saveAttributes(array('email_verified' => 1))) {
				return false;
			}
			return true;
		}
		return false;
	}
	
	public function getEmail()
	{
		return $this->email;
	}

	public function save()
	{
		if ($this->_id === null) {
			$record = new User;
			$record->password = 'x';
		} else {
			$record = User::model()->findByPk($this->_id);
		}
		if ($record!==null) {
			$record->setAttributes(array(
				'username' => $this->username,
				'email' => $this->email,
				'firstname' => $this->firstName,
				'lastname' => $this->lastName,
				'is_active' => 1,
			));
			if ($record->save()) {
				$this->_id = $record->getPrimaryKey();
				return true;
			}
			Yii::log('Failed to save user: '.print_r($record->getErrors(),true), 'warning');
		} else {
			Yii::log('Trying to save UserIdentity but no matching User has been found', 'warning');
		}
		return false;
	}

	public function setAttributes(array $attributes)
	{
		$allowedAttributes = array('username','email','firstName','lastName');
		foreach($attributes as $name=>$value) {
			if (in_array($name, $allowedAttributes))
				$this->$name = $value;
		}
		return true;
	}

	public function getAttributes()
	{
		return array(
			'username' => $this->username,
			'email' => $this->email,
			'firstName' => $this->firstName,
			'lastName' => $this->lastName,
		);
	}

	public function getOneTimePasswordSecret()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->one_time_password_secret;
		}
		return false;
	}

	public function setOneTimePasswordSecret($secret)
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->saveAttributes(array('one_time_password_secret' => $secret));
		}
		return false;
	}

	public function getOneTimePassword()
	{
		if ($this->_id===null)
			return array(null, null);
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return array(
				$record->one_time_password_code,
				$record->one_time_password_counter === null ? 1 : $record->one_time_password_counter,
			);
		}
		return array(null, null);
	}

	public function setOneTimePassword($password, $counter = 1)
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->saveAttributes(array(
				'one_time_password_code' => $password,
				'one_time_password_counter' => $counter,
			));
		}
		return false;
	}
}
