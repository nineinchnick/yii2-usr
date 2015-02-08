<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * PasswordForm class.
 * PasswordForm is the data structure for keeping password form data. It is used by the 'register' and 'profile' actions of 'DefaultController'.
 */
class PasswordForm extends BasePasswordForm
{
    public $password;

    /**
     * @var IdentityInterface cached object returned by @see getIdentity()
     */
    private $_identity;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        $rules = array_merge([
            ['password', 'trim', 'except' => 'register'],
            ['password', 'required', 'except' => 'register'],
            ['password', 'authenticate', 'except' => 'register'],
        ], parent::rules());

        return $rules;
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'password' => Yii::t('usr', 'Current password'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getIdentity()
    {
        if ($this->_identity === null) {
            if ($this->scenario === 'register') {
                return $this->_identity;
            }
            $this->_identity = $this->webUser->getIdentity();
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
    public function authenticate($attribute, $params)
    {
        if ($this->hasErrors()) {
            return false;
        }
        if (($identity = $this->getIdentity()) === null) {
            throw new \yii\base\Exception('Current user has not been found in the database.');
        }
        if (!$identity->verifyPassword($this->$attribute)) {
            $this->addError($attribute, Yii::t('usr', 'Invalid password.'));

            return false;
        }

        return true;
    }

    /**
     * Resets user password using the new one given in the model.
     * @return boolean whether password reset was successful
     */
    public function resetPassword($identity = null)
    {
        if ($this->hasErrors()) {
            return false;
        }
        if ($identity === null) {
            $identity = $this->getIdentity();
        }
        $identity->password = $this->password;
        if (($message = $identity->resetPassword($this->newPassword)) !== true) {
            $this->addError('newPassword', is_string($message) ? $message : Yii::t('usr', 'Failed to reset the password.'));

            return false;
        }

        return true;
    }
}
