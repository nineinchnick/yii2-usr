<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * RecoveryForm class.
 * RecoveryForm is the data structure for keeping
 * password recovery form data. It is used by the 'recovery' action of 'DefaultController'.
 */
class RecoveryForm extends BasePasswordForm
{
    public $username;
    public $email;
    public $activationKey;

    /**
     * @var IdentityInterface cached object returned by @see getIdentity()
     */
    private $_identity;

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        $rules = $this->filterRules(array_merge([
            [['username', 'email'], 'trim'],
            [['username', 'email'], 'default'],
            [['username', 'email'], 'existingIdentity'],
            ['email', 'email'],

            ['activationKey', 'trim', 'on' => ['reset', 'verify']],
            ['activationKey', 'default', 'on' => ['reset', 'verify']],
            ['activationKey', 'required', 'on' => ['reset', 'verify']],
            ['activationKey', 'validActivationKey', 'on' => ['reset', 'verify']],
        ], $this->rulesAddScenario(parent::rules(), 'reset')));

        return $rules;
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'username'        => Yii::t('usr', 'Username'),
            'email'            => Yii::t('usr', 'Email'),
            'activationKey'    => Yii::t('usr', 'Activation Key'),
        ], $this->getBehaviorLabels());
    }

    /**
     * @inheritdoc
     */
    public function getIdentity()
    {
        if ($this->_identity === null) {
            // generate a fake object just to check if it implements a correct interface
            $identityClass = $this->webUser->identityClass;
            $fakeIdentity = new $identityClass(null, null);
            if (!($fakeIdentity instanceof \nineinchnick\usr\components\ActivatedIdentityInterface)) {
                throw new \yii\base\Exception(Yii::t('usr', 'The {class} class must implement the {interface} interface.', ['class' => $identityClass, 'interface' => '\nineinchnick\usr\components\ActivatedIdentityInterface']));
            }
            $attributes = [];
            if ($this->username !== null) {
                $attributes['username'] = $this->username;
            }
            if ($this->email !== null) {
                $attributes['email'] = $this->email;
            }
            if (!empty($attributes)) {
                $this->_identity = $identityClass::find()->where($attributes)->one();
            }
        }

        return $this->_identity;
    }

    /**
     * Inline validator that checks if an identity exists matching provided username or password.
     * @param  string  $attribute
     * @param  array   $params
     * @return boolean
     */
    public function existingIdentity($attribute, $params)
    {
        if ($this->hasErrors()) {
            return false;
        }
        $identity = $this->getIdentity();
        if ($identity === null) {
            if ($this->username !== null) {
                $this->addError('username', Yii::t('usr', 'No user found matching this username.'));
            } elseif ($this->email !== null) {
                $this->addError('email', Yii::t('usr', 'No user found matching this email address.'));
            } else {
                $this->addError('username', Yii::t('usr', 'Please specify username or email.'));
            }

            return false;
        } elseif ($identity->isDisabled()) {
            $this->addError('username', Yii::t('usr', 'User account has been disabled.'));

            return false;
        }

        return true;
    }

    /**
     * Validates the activation key.
     */
    public function validActivationKey($attribute, $params)
    {
        if ($this->hasErrors()) {
            return false;
        }
        if (($identity = $this->getIdentity()) === null) {
            return false;
        }

        $errorCode = $identity->verifyActivationKey($this->activationKey);
        switch ($errorCode) {
            default:
            case $identity::ERROR_AKEY_INVALID:
                $this->addError('activationKey', Yii::t('usr', 'Activation key is invalid.'));

                return false;
            case $identity::ERROR_AKEY_TOO_OLD:
                $this->addError('activationKey', Yii::t('usr', 'Activation key is too old.'));

                return false;
            case $identity::ERROR_AKEY_NONE:
                return true;
        }

        return true;
    }

    /**
     * Resets user password using the new one given in the model.
     * @return boolean whether password reset was successful
     */
    public function resetPassword()
    {
        $identity = $this->getIdentity();
        if (($message = $identity->resetPassword($this->newPassword)) !== true) {
            $this->addError('newPassword', is_string($message) ? $message : Yii::t('usr', 'Failed to reset the password.'));

            return false;
        }

        return true;
    }

    /**
     * Logs in the user using the given username and new password.
     * @return boolean whether login is successful
     */
    public function login()
    {
        $identity = $this->getIdentity();

        if ($identity->authenticate($this->newPassword)) {
            return $this->webUser->login($identity, 0);
        }

        return false;
    }
}
