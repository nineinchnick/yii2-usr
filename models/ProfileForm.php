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
    public $picture;
    public $removePicture;
    public $password;

    /**
     * @var IdentityInterface cached object returned by @see getIdentity()
     */
    private $_identity;

    /**
     * @var array Picture upload validation rules.
     */
    private $_pictureUploadRules;

    /**
     * Returns rules for picture upload or an empty array if they are not set.
     * @return array
     */
    public function getPictureUploadRules()
    {
        return $this->_pictureUploadRules === null ? [] : $this->_pictureUploadRules;
    }

    /**
     * Sets rules to validate uploaded picture. Rules should NOT contain attribute name as this method adds it.
     * @param array $rules
     */
    public function setPictureUploadRules($rules)
    {
        $this->_pictureUploadRules = [];
        if (!is_array($rules)) {
            return;
        }
        foreach ($rules as $rule) {
            $this->_pictureUploadRules[] = array_merge(['picture'], $rule);
        }
    }

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return $this->filterRules(array_merge([
            [['username', 'email', 'firstName', 'lastName', 'removePicture'], 'trim'],
            [['username', 'email', 'firstName', 'lastName', 'removePicture'], 'default'],

            [['username', 'email'], 'required'],
            [['username', 'email'], 'uniqueIdentity'],
            ['email', 'email'],

            ['removePicture', 'boolean'],
            ['password', 'validCurrentPassword', 'except' => ['register', 'manage'], 'skipOnEmpty' => false],
        ], $this->pictureUploadRules));
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        if (!isset($scenarios['register'])) {
            $scenarios['register'] = $scenarios[self::SCENARIO_DEFAULT];
        }
        if (!isset($scenarios['manage'])) {
            $scenarios['manage'] = $scenarios[self::SCENARIO_DEFAULT];
        }

        return $scenarios;
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return array_merge($this->getBehaviorLabels(), [
            'username'      => Yii::t('usr', 'Username'),
            'email'         => Yii::t('usr', 'Email'),
            'firstName'     => Yii::t('usr', 'First name'),
            'lastName'      => Yii::t('usr', 'Last name'),
            'picture'       => Yii::t('usr', 'Profile picture'),
            'removePicture' => Yii::t('usr', 'Remove picture'),
            'password'      => Yii::t('usr', 'Current password'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getIdentity()
    {
        if ($this->_identity === null) {
            if ($this->scenario === 'register' || $this->scenario === 'manage') {
                $identityClass = $this->webUser->identityClass;
                $this->_identity = new $identityClass();
                $this->_identity->loadDefaultValues();
            } else {
                $this->_identity = $this->webUser->getIdentity();
            }
            if ($this->_identity !== null
                && !($this->_identity instanceof \nineinchnick\usr\components\EditableIdentityInterface)
            ) {
                throw new \yii\base\Exception(
                    Yii::t('usr', 'The {class} class must implement the {interface} interface.', [
                        'class' => get_class($this->_identity),
                        'interface' => '\nineinchnick\usr\components\EditableIdentityInterface',
                    ])
                );
            }
        }

        return $this->_identity;
    }

    public function setIdentity($identity)
    {
        $this->_identity = $identity;
    }

    public function uniqueIdentity($attribute, $params)
    {
        if ($this->hasErrors()) {
            return false;
        }
        $identityClass = $this->webUser->identityClass;
        $existingIdentity = $identityClass::find()->where([$attribute => $this->$attribute])->one();
        if ($existingIdentity !== null
            && ($this->scenario == 'register'
                || (($identity = $this->getIdentity()) !== null && $existingIdentity->getId() != $identity->getId()))
        ) {
            $this->addError($attribute, Yii::t('usr', '{attribute} has already been used by another user.', [
                'attribute' => $this->$attribute,
            ]));

            return false;
        }

        return true;
    }

    /**
     * A valid current password is required only when changing email.
     */
    public function validCurrentPassword($attribute, $params)
    {
        if ($this->hasErrors()) {
            return false;
        }
        if (($identity = $this->getIdentity()) === null) {
            throw new \yii\base\Exception('Current user has not been found in the database.');
        }
        if ($identity->getEmail() === $this->email) {
            return true;
        }
        if (!$identity->authenticate($this->$attribute)) {
            $this->addError($attribute, Yii::t('usr', 'Changing email address requires providing the current password.'));

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

        return $this->webUser->login($identity, 0);
    }

    /**
     * Updates the identity with this models attributes and saves it.
     * @param  boolean $requireVerifiedEmail the Usr module property
     * @return boolean whether saving is successful
     */
    public function save($requireVerifiedEmail = false)
    {
        if (($identity = $this->getIdentity()) === null) {
            return false;
        }
        $attributes = $this->getAttributes();
        unset($attributes['owner'], $attributes['webUser'], $attributes['picture'], $attributes['removePicture'], $attributes['password']);
        $identity->setIdentityAttributes($attributes);
        if ($identity->saveIdentity($requireVerifiedEmail)) {
            if ((!($this->picture instanceof \yii\web\UploadedFile) || $identity->savePicture($this->picture)) && (!$this->removePicture || $identity->removePicture())) {
                $this->_identity = $identity;

                return true;
            }
        }

        return false;
    }
}
