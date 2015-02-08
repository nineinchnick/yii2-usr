<?php

namespace nineinchnick\usr\models;

use nineinchnick\usr\components\IdentityInterface;
use nineinchnick\usr\components\PasswordHistoryIdentityInterface;
use Yii;

/**
 * BasePasswordForm class.
 * BasePasswordForm is the base class for forms used to set new password.
 */
abstract class BasePasswordForm extends BaseUsrForm
{
    public $newPassword;
    public $newVerify;

    /**
     * @var array Password strength validation rules.
     */
    private $_passwordStrengthRules;

    /**
     * Returns default password strength rules. This is called from the rules() method.
     * If no rules has been set in the module configuration, uses sane defaults
     * of 8 characters containing at least one capital, lower case letter and number.
     * @return array
     */
    public function getPasswordStrengthRules()
    {
        if ($this->_passwordStrengthRules === null) {
            $this->_passwordStrengthRules = [
                ['newPassword', 'string', 'min' => 8, 'message' => Yii::t('usr', 'New password must contain at least 8 characters.')],
                ['newPassword', 'match', 'pattern' => '/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', 'message'    => Yii::t('usr', 'New password must contain at least one lower and upper case character and a digit.')],
            ];
        }

        return $this->_passwordStrengthRules;
    }

    /**
     * Sets rules to validate password strength. Rules should NOT contain attribute name as this method adds it.
     * @param array $rules
     */
    public function setPasswordStrengthRules($rules)
    {
        $this->_passwordStrengthRules = [];
        if (!is_array($rules)) {
            return;
        }
        foreach ($rules as $rule) {
            $this->_passwordStrengthRules[] = array_merge(['newPassword'], $rule);
        }
    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     * @return array
     */
    public function rules()
    {
        $rules = array_merge(
            [
                [['newPassword', 'newVerify'], 'trim'],
                [['newPassword', 'newVerify'], 'required'],
                ['newPassword', 'unusedNewPassword'],
            ],
            $this->passwordStrengthRules,
            [
                ['newVerify', 'compare', 'compareAttribute' => 'newPassword', 'message' => Yii::t('usr', 'Please type the same new password twice to verify it.')],
            ]
        );

        return $rules;
    }

    /**
     * Adds specified scenario to the given set of rules.
     * @param  array  $rules
     * @param  string $scenario
     * @return array
     */
    public function rulesAddScenario(array $rules, $scenario)
    {
        foreach ($rules as $key => $rule) {
            $rules[$key]['on'] = $scenario;
        }

        return $rules;
    }

    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'newPassword'    => Yii::t('usr', 'New password'),
            'newVerify'        => Yii::t('usr', 'Verify'),
        ]);
    }

    /**
     * Depending on context, could return a new or existing identity
     * or the identity of currently logged in user.
     * @return IdentityInterface
     */
    abstract public function getIdentity();

    /**
     * @return boolean whether password reset was successful
     */
    abstract public function resetPassword();

    /**
     * Checkes if current password hasn't been used before.
     * This is the 'unusedNewPassword' validator as declared in rules().
     * @return boolean
     */
    public function unusedNewPassword()
    {
        if ($this->hasErrors()) {
            return false;
        }

        /** @var IdentityInterface */
        $identity = $this->getIdentity();
        // check if new password hasn't been used before
        if ($identity instanceof PasswordHistoryIdentityInterface) {
            if (($lastUsed = $identity->getPasswordDate($this->newPassword)) !== null) {
                $this->addError('newPassword', Yii::t('usr', 'New password has been used before, last set on {date}.', ['date' => $lastUsed]));

                return false;
            }

            return true;
        }
        // check if new password is not the same as current one
        if (property_exists($this, 'password')) {
            if ($this->password === $this->newPassword) {
                $this->addError('newPassword', Yii::t('usr', 'New password must be different than the old one.'));

                return false;
            }
        }

        return true;
    }
}
