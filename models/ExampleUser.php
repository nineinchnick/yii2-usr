<?php

namespace nineinchnick\usr\models;

use Yii;
use yii\helpers\Security;
use nineinchnick\usr\components;

/**
 * This is the model class for table "{{users}}".
 *
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string $activation_key
 * @property datetime $created_on
 * @property datetime $updated_on
 * @property datetime $last_visit_on
 * @property datetime $password_set_on
 * @property boolean $email_verified
 * @property boolean $is_active
 * @property boolean $is_disabled
 * @property string $one_time_password_secret
 * @property string $one_time_password_code
 * @property integer $one_time_password_counter
 *
 * The followings are the available model relations:
 * @property UserRemoteIdentity[] $userRemoteIdentities
 * @property UserUsedPassword[] $userUsedPassword
 */
abstract class ExampleUser extends \yii\db\ActiveRecord implements components\IdentityInterface, components\ActivatedIdentityInterface, components\EditableIdentityInterface
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%users}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		// password is unsafe on purpose, assign it manually after hashing only if not empty
		return [
			[['username', 'email', 'firstname', 'lastname', 'is_active', 'is_disabled'], 'filter', 'filter' => 'trim'],
			[['activation_key', 'created_on', 'updated_on', 'last_visit_on', 'password_set_on', 'email_verified'], 'filter', 'filter' => 'trim', 'on' => 'search'],
			[['username', 'email', 'firstname', 'lastname', 'is_active', 'is_disabled'], 'default'],
			[['activation_key', 'created_on', 'updated_on', 'last_visit_on', 'password_set_on', 'email_verified'], 'default', 'on' => 'search'],
			[['username', 'email', 'is_active', 'is_disabled', 'email_verified'], 'required', 'except' => 'search'],
			[['created_on', 'updated_on', 'last_visit_on', 'password_set_on'], 'date', 'format' => ['yyyy-MM-dd', 'yyyy-MM-dd HH:mm', 'yyyy-MM-dd HH:mm:ss'], 'on' => 'search'],
			['activation_key', 'string', 'max'=>128, 'on' => 'search'],
			[['is_active', 'is_disabled', 'email_verified'], 'boolean'],
			[['username', 'email'], 'unique', 'except' => 'search'],
		];
	}

	public function getUserRemoteIdentities()
	{
		return $this->hasMany(UserRemoteIdentity::className(), ['user_id' => 'id']);
	}

	public function getUserUsedPasswords()
	{
		return $this->hasMany(UserUsedPassword::className(), ['user_id' => 'id'])->orderBy('set_on DESC');
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('models', 'ID'),
			'username' => Yii::t('models', 'Username'),
			'password' => Yii::t('models', 'Password'),
			'email' => Yii::t('models', 'Email'),
			'firstname' => Yii::t('models', 'Firstname'),
			'lastname' => Yii::t('models', 'Lastname'),
			'activation_key' => Yii::t('models', 'Activation Key'),
			'created_on' => Yii::t('models', 'Created On'),
			'updated_on' => Yii::t('models', 'Updated On'),
			'last_visit_on' => Yii::t('models', 'Last Visit On'),
			'password_set_on' => Yii::t('models', 'Password Set On'),
			'email_verified' => Yii::t('models', 'Email Verified'),
			'is_active' => Yii::t('models', 'Is Active'),
			'is_disabled' => Yii::t('models', 'Is Disabled'),
			'one_time_password_secret' => Yii::t('models', 'One Time Password Secret'),
			'one_time_password_code' => Yii::t('models', 'One Time Password Code'),
			'one_time_password_counter' => Yii::t('models', 'One Time Password Counter'),
		];
	}


	public function beforeSave($insert)
	{
		if ($insert) {
			$this->created_on = date('Y-m-d H:i:s');
		} else {
			$this->updated_on = date('Y-m-d H:i:s');
		}
		return parent::beforeSave($insert);
	}

	// {{{ IdentityInterface

	public static function findIdentity($id)
	{
		return self::find($id);
	}

	public function getId()
	{
		return $this->id;
	}

	public function getAuthKey()
	{
		return Security::hashData($this->id,$this->password);
	}

	public function validateAuthKey($authKey)
	{
		return Security::validateData($authKey,$this->password);
	}

	/**
	 * @inheritdoc
	 */
	public function authenticate()
	{
		if ($this->is_active && !$this->is_disabled && $this->verifyPassword($this->password)) {
			$this->last_visit_on = date('Y-m-d H:i:s');
			$this->save(false);
			return true;
		} else {
			return false;
		}
	}

	// }}}

	public static function findByUsername($username)
	{
		return self::find(['username'=>$username]);
	}

	public function validatePassword($password)
	{
		return Security::validatePassword($password, $this->password);
	}

	// {{{ PasswordHistoryIdentityInterface

	public function getPasswordDate($password = null)
	{
		if ($password === null) {
			return $this->password_set_on;
		} else {
			foreach($this->userUsedPasswords as $usedPassword) {
				if ($usedPassword->verifyPassword($password))
					return $usedPassword->set_on;
			}
		}
		return null;
	}

	public function resetPassword($password)
	{
		$hashedPassword = Security::generatePasswordHash($password);
		$usedPassword = new \app\models\UserUsedPassword;
		$usedPassword->setAttributes([
			'user_id'=>$this->id,
			'password'=>$hashedPassword,
			'set_on'=>date('Y-m-d H:i:s'),
		], false);
		$this->setAttributes([
			'password'=>$hashedPassword,
			'password_set_on'=>date('Y-m-d H:i:s'),
		], false);
		return $usedPassword->save() && $this->save();
	}

	// }}}

	// {{{ EditableIdentityInterface

	/**
	 * Maps the ProfileForm attributes to this model's attributes
	 * @return array
	 */
	protected function identityAttributesMap()
	{
		// notice the capital N in name
		return ['username' => 'username', 'email' => 'email', 'firstName' => 'firstname', 'lastName' => 'lastname'];
	}

	public function saveIdentity()
	{
		if ($this->isNewRecord) {
			$this->password = 'x';
			$this->is_active = 1;
			$this->is_disabled = 0;
			$this->email_verified = 0;
		}
		if (!$this->save()) {
			Yii::warning('Failed to save user: '.print_r($this->getErrors(),true), 'usr');
			return false;
		}
		return true;
	}

	public function setIdentityAttributes(array $attributes)
	{
		$allowedAttributes = $this->identityAttributesMap();
		foreach($attributes as $name=>$value) {
			if (isset($allowedAttributes[$name])) {
				$key = $allowedAttributes[$name];
				$this->$key = $value;
			}
		}
		return true;
	}

	public function getIdentityAttributes()
	{
		$allowedAttributes = array_flip($this->identityAttributesMap());
		$result = array();
		foreach($this->getAttributes() as $name=>$value) {
			if (isset($allowedAttributes[$name])) {
				$result[$allowedAttributes[$name]] = $value;
			}
		}
		return $result;
	}

	// }}}

	// {{{ ActivatedIdentityInterface

	public function isActive()
	{
		return (bool)$this->is_active;
	}

	public function isDisabled()
	{
		return (bool)$this->is_disabled;
	}

	public function getActivationKey()
	{
		$this->activation_key = md5(time().mt_rand().$this->username);
		return $this->save(false) ? $this->activation_key : false;
	}

	public function verifyActivationKey($activationKey)
	{
		return $this->activation_key === $activationKey ? self::ERROR_AKEY_NONE : self::ERROR_AKEY_INVALID;
	}

	public function verifyEmail()
	{
		$this->email_verified = 1;
		return $this->save(false);
	}
	
	public function getEmail()
	{
		return $this->email;
	}

	// }}}

	// {{{ OneTimePasswordIdentityInterface

	public function getOneTimePasswordSecret()
	{
		return $this->one_time_password_secret;
	}

	public function setOneTimePasswordSecret($secret)
	{
		$this->one_time_password_secret = $secret;
		return $this->save(false);
	}

	public function getOneTimePassword()
	{
		return array(
			$this->one_time_password_code,
			$this->one_time_password_counter === null ? 1 : $this->one_time_password_counter,
		);
	}

	public function setOneTimePassword($password, $counter = 1)
	{
		$this->one_time_password_code = $password;
		$this->one_time_password_counter = $counter;
		return $this->save(false);
	}

	// }}}

	// {{{ HybridauthIdentityInterface

	public static function findByProvider($provider, $identifier)
	{
		return User::find()
			->with('userRemoteIdentities')
			->andWhere('userRemoteIdentities.provider=:provider',[':provider'=>$provider])
			->andWhere('userRemoteIdentities.identifier=:identifier',[':identifer'=>$identifier])
			->one();
	}

	public function addRemoteIdentity($provider, $identifier)
	{
		$model = new UserRemoteIdentity;
		$model->setAttributes(array(
			'user_id' => $this->id,
			'provider' => $provider,
			'identifier' => $identifier,
		), false);
		return $model->save();
	}

	// }}}
}
