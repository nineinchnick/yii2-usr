<?php

namespace nineinchnick\yii2-usr\models;

use Yii;
use \yii\helpers\Security;

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
abstract class ExampleUser extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{users}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		// password is unsafe on purpose, assign it manually after hashing only if not empty
		return [
			['username, email, firstname, lastname, is_active, is_disabled', 'filter', 'filter' => 'trim'],
			['activation_key, created_on, updated_on, last_visit_on, password_set_on, email_verified', 'filter', 'filter' => 'trim', 'on' => 'search'],
			['username, email, firstname, lastname, is_active, is_disabled', 'default', 'setOnEmpty' => true, 'value' => null],
			['activation_key, created_on, updated_on, last_visit_on, password_set_on, email_verified', 'default', 'setOnEmpty' => true, 'value' => null, 'on' => 'search'],
			['username, email, is_active, is_disabled, email_verified', 'required', 'except' => 'search'],
			['created_on, updated_on, last_visit_on, password_set_on', 'date', 'format' => ['yyyy-MM-dd', 'yyyy-MM-dd HH:mm', 'yyyy-MM-dd HH:mm:ss'], 'on' => 'search'],
			['activation_key', 'string', 'max'=>128, 'on' => 'search'],
			['is_active, is_disabled, email_verified', 'boolean'],
			['username, email', 'unique', 'except' => 'search'],
		];
	}

	public function getUserRemoteIdentities()
	{
		return $this->hasMany(UserRemoteIdentity::className(), ['user_id' => 'id']);
	}

	public function getUserRemoteIdentities()
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

	// {{{ Identity

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

	// }}}

	public static function findByUsername($username)
	{
		return self::find(array('username'=>$username));
	}

	public function validatePassword($password)
	{
		return Security::validatePassword($password, $this->password);
	}

}
