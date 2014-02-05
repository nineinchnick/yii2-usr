<?php

namespace nineinchnick\usr\models;

use Yii;
use yii\helpers\Security;
use nineinchnick\usr\components;
use app\models\UserUsedPassword;
use app\models\UserRemoteIdentity;
use app\models\UserProfilePicture;

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
 * @property UserProfilePicture[] $userProfilePictures
 */
abstract class ExampleUser extends \yii\db\ActiveRecord
	implements
	components\IdentityInterface,
	components\ActivatedIdentityInterface,
	components\EditableIdentityInterface,
	components\OneTimePasswordIdentityInterface,
	components\PasswordHistoryIdentityInterface,
	components\HybridauthIdentityInterface,
	components\PictureIdentityInterface
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

	public function getUserProfilePictures()
	{
		return $this->hasMany(UserProfilePicture::className(), ['user_id' => 'id']);
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

	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if ($insert) {
			$this->created_on = date('Y-m-d H:i:s');
		} else {
			$this->updated_on = date('Y-m-d H:i:s');
		}
		return parent::beforeSave($insert);
	}

	/**
	 * Finds an identity by the given username.
	 *
	 * @param string $username the username to be looked for
	 * @return IdentityInterface|null the identity object that matches the given ID.
	 */
	public static function findByUsername($username)
	{
		return self::find(['username'=>$username]);
	}

	/**
	 * @param string $password password to validate
	 * @return bool if password provided is valid for current user
	 */
	public function verifyPassword($password)
	{
		try {
			return Security::validatePassword($password, $this->password);
		} catch(yii\base\InvalidParamException $e) {
			return false;
		}
	}

	// {{{ IdentityInterface

	/**
	 * Finds an identity by the given ID.
	 *
	 * @param string|integer $id the ID to be looked for
	 * @return IdentityInterface|null the identity object that matches the given ID.
	 */
	public static function findIdentity($id)
	{
		return self::find($id);
	}

	/**
	 * @return int|string current user ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string current user auth key
	 */
	public function getAuthKey()
	{
		return Security::hashData($this->id,$this->password);
	}

	/**
	 * @param string $authKey
	 * @return boolean if auth key is valid for current user
	 */
	public function validateAuthKey($authKey)
	{
		return Security::validateData($authKey,$this->getAuthKey());
	}

	/**
	 * @inheritdoc
	 */
	public function authenticate($password)
	{
		if ($this->is_active && !$this->is_disabled && $this->verifyPassword($password)) {
			$this->last_visit_on = date('Y-m-d H:i:s');
			$this->save(false);
			return true;
		} else {
			return false;
		}
	}

	// }}}

	// {{{ PasswordHistoryIdentityInterface

	/**
	 * Returns the date when specified password was last set or null if it was never used before.
	 * If null is passed, returns date of setting current password.
	 * @param string $password new password or null if checking when the current password has been set
	 * @return string date in YYYY-MM-DD format or null if password was never used.
	 */
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

	/**
	 * Changes the password and updates last password change date.
	 * Saves old password so it couldn't be used again.
	 * @param string $password new password
	 * @return boolean
	 */
	public function resetPassword($password)
	{
		$hashedPassword = Security::generatePasswordHash($password);
		$usedPassword = new UserUsedPassword;
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
	 * Maps the \nineinchnick\usr\models\ProfileForm attributes to the identity attributes
	 * @see \nineinchnick\usr\models\ProfileForm::attributes()
	 * @return array
	 */
	public function identityAttributesMap()
	{
		// notice the capital N in name
		return ['username' => 'username', 'email' => 'email', 'firstName' => 'firstname', 'lastName' => 'lastname'];
	}

	/**
	 * Saves a new or existing identity. Does not set or change the password.
	 * @see PasswordHistoryIdentityInterface::resetPassword()
	 * Should detect if the email changed and mark it as not verified.
	 * @param boolean $requireVerifiedEmail
	 * @return boolean
	 */
	public function saveIdentity($requireVerifiedEmail=false)
	{
		if ($this->isNewRecord) {
			$this->password = 'x';
			$this->is_active = $requireVerifiedEmail ? 0 : 1;
			$this->is_disabled = 0;
			$this->email_verified = 0;
		}
		if (!$this->save()) {
			Yii::warning('Failed to save user: '.print_r($this->getErrors(),true), 'usr');
			return false;
		}
		return true;
	}

	/**
	 * Sets attributes like username, email, first and last name.
	 * Password should be changed using only the resetPassword() method from the PasswordHistoryIdentityInterface.
	 * @param array $attributes
	 * @return boolean
	 */
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

	/**
	 * Returns attributes like username, email, first and last name.
	 * @return array
	 */
	public function getIdentityAttributes()
	{
		$allowedAttributes = array_flip($this->identityAttributesMap());
		$result = [];
		foreach($this->getAttributes() as $name=>$value) {
			if (isset($allowedAttributes[$name])) {
				$result[$allowedAttributes[$name]] = $value;
			}
		}
		return $result;
	}

	// }}}

	// {{{ ActivatedIdentityInterface

	/**
	 * Checkes if user account is active. This should not include disabled (banned) status.
	 * This could include if the email address has been verified.
	 * Same checks should be done in the authenticate() method, because this method is not called before logging in.
	 * @return boolean
	 */
	public function isActive()
	{
		return (bool)$this->is_active;
	}

	/**
	 * Checkes if user account is disabled (banned). This should not include active status.
	 * @return boolean
	 */
	public function isDisabled()
	{
		return (bool)$this->is_disabled;
	}

	/**
	 * Generates and saves a new activation key used for verifying email and restoring lost password.
	 * The activation key is then sent by email to the user.
	 *
	 * Note: only the last generated activation key should be valid and an activation key
	 * should have it's generation date saved to verify it's age later.
	 *
	 * @return string
	 */
	public function getActivationKey()
	{
		$this->activation_key = Security::generateRandomKey();
		return $this->save(false) ? $this->activation_key : false;
	}

	/**
	 * Verifies if specified activation key matches the saved one and if it's not too old.
	 * This method should not alter any saved data.
	 * @return integer the verification error code. If there is an error, the error code will be non-zero.
	 */
	public function verifyActivationKey($activationKey)
	{
		return $this->activation_key === $activationKey ? self::ERROR_AKEY_NONE : self::ERROR_AKEY_INVALID;
	}

	/**
	 * Verify users email address, which could also activate his account and allow him to log in.
	 * Call only after verifying the activation key.
	 * @param boolean $requireVerifiedEmail
	 * @return boolean
	 */
	public function verifyEmail($requireVerifiedEmail=false)
	{
		if ($this->email_verified) {
			return true;
		}
		$this->email_verified = 1;
		if ($requireVerifiedEmail && !$this->is_active) {
			$this->is_active = 1;
		}
		return $this->save(false);
	}
	
	/**
	 * Returns user email address.
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	// }}}

	// {{{ OneTimePasswordIdentityInterface

	/**
	 * Returns current secret used to generate one time passwords. If it's null, two step auth is disabled.
	 * @return string
	 */
	public function getOneTimePasswordSecret()
	{
		return $this->one_time_password_secret;
	}

	/**
	 * Sets current secret used to generate one time passwords. If it's null, two step auth is disabled.
	 * @param string $secret
	 * @return boolean
	 */
	public function setOneTimePasswordSecret($secret)
	{
		if ($this->getIsNewRecord())
			return false;
		$this->one_time_password_secret = $secret;
		return $this->save(false);
	}

	/**
	 * Returns previously used one time password and value of counter used to generate current one time password, used in counter mode.
	 * @return array [string, integer] 
	 */
	public function getOneTimePassword()
	{
		return [
			$this->one_time_password_code,
			$this->one_time_password_counter === null ? 1 : $this->one_time_password_counter,
		];
	}

	/**
	 * Sets previously used one time password and value of counter used to generate current one time password, used in counter mode.
	 * @return boolean
	 */
	public function setOneTimePassword($password, $counter = 1)
	{
		if ($this->getIsNewRecord())
			return false;
		$this->one_time_password_code = $password;
		$this->one_time_password_counter = $counter;
		return $this->save(false);
	}

	// }}}

	// {{{ HybridauthIdentityInterface

	/**
	 * Loads a specific user identity connected to specified provider by an identifier.
	 * @param string $provider
	 * @param string $identifier
	 * @return object a user identity object or null if not found.
	 */
	public static function findByProvider($provider, $identifier)
	{
		$t = UserRemoteIdentity::tableName();
		return self::find()
			->leftJoin($t, self::tableName().'.[['.self::primaryKey()[0].']]='.$t.'.[[user_id]]')
			->andWhere($t.'.[[provider]]=:provider',[':provider'=>$provider])
			->andWhere($t.'.[[identifier]]=:identifier',[':identifier'=>$identifier])
			->one();
	}

	/**
	 * Associates this identity with a remote one identified by a provider name and identifier.
	 * @param string $provider
	 * @param string $identifier
	 * @return boolean
	 */
	public function addRemoteIdentity($provider, $identifier)
	{
		$model = new UserRemoteIdentity;
		$model->setAttributes([
			'user_id' => $this->id,
			'provider' => $provider,
			'identifier' => $identifier,
		], false);
		return $model->save();
	}

	// }}}

	// {{{ PictureIdentityInterface

	/**
	 * @inheritdoc
	 */
	public function savePicture($picture)
	{
		if ($this->getIsNewRecord())
			return false;
		$pictureRecord = $this->getUserProfilePictures()->andWhere('original_picture_id IS NULL')->all();
		if (!empty($pictureRecord)) {
			$pictureRecord = $pictureRecord[0];
		} else {
			$pictureRecord = new UserProfilePicture;
			$pictureRecord->user_id = $this->id;
		}
		$picturePath = $picture->getTempName();
		$pictureRecord->filename = $picture;
		$pictureRecord->mimetype = yii\helpers\FileHelper::getMimeType($picturePath);
		$pictureRecord->contents = base64_encode(file_get_contents($picturePath));

		if (($size = @getimagesize($picturePath)) !== false) {
			list($width, $height, $type, $attr) = $size;
			$pictureRecord->width = $width;
			$pictureRecord->height = $height;
		} else {
			$pictureRecord->width = 0;
			$pictureRecord->height = 0;
		}
		return $pictureRecord->save() && $this->saveThumbnail($picture, $pictureRecord);
	}

	protected function saveThumbnail($picture, $pictureRecord)
	{
		// skip thumbnail if couldn't read size of original picture
		if ($pictureRecord->width == 0 || $pictureRecord->height == 0) {
			return true;
		}
		// calculate thumbnail dimensions with max width and height at 80
		$max_width = 80;
		$max_height = 80;

		$width = $pictureRecord->width;
		$height = $pictureRecord->height;
		if ($width > $max_width || $height > $max_height ) {
			if ($width > $height) {
				$height = floor($height / ($width / $max_width));
				$width = $max_width;
			} else {
				$width = floor($width / ($height / $max_height));
				$height = $max_height;
			}
		}

		// create the thumbnail image (always a jpeg)
		$thumbImage = imagecreatetruecolor($width, $height);
		$sourceImage = imagecreatefromstring(base64_decode($pictureRecord->contents));
		imagecopyresized($thumbImage, $sourceImage, 0, 0, 0, 0, $width, $height, $pictureRecord->width, $pictureRecord->height);
		ob_start();
		imagejpeg($thumbImage);
		$contents = ob_get_clean();

		// update existing thumbnail or create a new one
		$thumbnail = $pictureRecord->thumbnails;
		if (!empty($thumbnail)) {
			$thumbnail = $thumbnail[0];
		} else {
			$thumbnail = new UserProfilePicture;
			$thumbnail->original_picture_id = $pictureRecord->id;
			$thumbnail->user_id = $pictureRecord->user_id;
			$thumbnail->filename = $pictureRecord->filename;
			$thumbnail->mimetype = 'image/jpeg';
		}
		$thumbnail->width = $width;
		$thumbnail->height = $height;
		$thumbnail->contents = base64_encode($contents);
		return $thumbnail->save();
	}

	/**
	 * @inheritdoc
	 */
	public function getPictureUrl($width=null, $height=null)
	{
		if ($this->getIsNewRecord())
			return false;
		// try to locate biggest picture smaller than specified dimensions
		$query = $this->getUserProfilePictures()->select('id')->orderBy('width DESC')->limit(1);
		if ($width !== null && $height !== null) {
			$query->andWhere('width <= :width AND height <= :height', [':width'=>$width, ':height'=>$height]);
		}
		$pictures = $query->all();
		if (!empty($pictures)) {
			return [
				'url'	=> Yii::$app->createAbsoluteUrl('/usr/profilePicture', ['id'=>$pictures[0]->id]),
				'width'	=> $pictures[0]->width,
				'height'=> $pictures[0]->height,
			];
		}

		// if no picture has been found, use a Gravatar
		$hash = md5(strtolower(trim($this->email)));
		// more at http://gravatar.com/site/implement/images/
		$options = [
			//'forcedefault' => 'y',
			'rating'=> 'g',
			'd'		=> 'retro',
			's'		=> $width,
		];
		$host = Yii::$app->request->isSecureConnection ? 'https://secure.gravatar.com' : 'http://gravatar.com';
		return [
			'url'	=> $host.'/avatar/'.$hash.'?'.http_build_query($options),
			'width'	=> $width,
			'height'=> $height,
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getPicture($id, $currentIdentity=true)
	{
		$criteria = new CDbCriteria;
		$criteria->addColumnCondition(['id'=>$id]);
		if ($currentIdentity) {
			$criteria->addColumnCondition(['user_id'=>$this->_id]);
		}
		if (($picture=UserProfilePicture::model()->find($criteria)) === null) {
			return null;
		}
		return [
			'mimetype'=>$picture->mimetype,
			'width'=>$picture->width,
			'height'=>$picture->height,
			'picture'=>base64_decode($picture->contents),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function removePicture($id=null)
	{
		if ($this->getIsNewRecord())
			return 0;
		$attributes = ['user_id'=>$this->id];
		if ($id !== null) {
			$attributes['id'] = $id;
		}
		return UserProfilePicture::model()->deleteAllByAttributes($attributes);
	}

	// }}}
}
