<?php
/**
 * ExpiredPasswordBehavior class file.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */

namespace nineinchnick\usr\components;

use Yii;
use DateTime;

/**
 * ExpiredPasswordBehavior adds captcha validation to a form model component.
 * The model should extend from {@link CFormModel} or its child classes.
 *
 * @property \yii\base\Model $owner The owner model that this behavior is attached to.
 * @property integer $passwordTimeout Number of days after which user is requred to reset his password after logging in.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class ExpiredPasswordBehavior extends FormModelBehavior
{
    private $_passwordTimeout;

    /**
     * @return integer Number of days after which user is requred to reset his password after logging in.
     */
    public function getPasswordTimeout()
    {
        return $this->_passwordTimeout;
    }

    /**
     * @param $value integer Number of days after which user is requred to reset his password after logging in.
     */
    public function setPasswordTimeout($value)
    {
        $this->_passwordTimeout = $value;
    }

    /**
     * @inheritdoc
     */
    public function filterRules($rules = [])
    {
        $behaviorRules = [
            ['password', 'passwordHasNotExpired', 'except' => ['reset', 'auth', 'verifyOTP']],
        ];

        return array_merge($rules, $this->applyRuleOptions($behaviorRules));
    }

    public function passwordHasNotExpired()
    {
        if ($this->owner->hasErrors()) {
            return false;
        }

        $identity = $this->owner->getIdentity();
        if (!($identity instanceof \nineinchnick\usr\components\PasswordHistoryIdentityInterface)) {
            throw new \yii\base\Exception(Yii::t('usr', 'The {class} class must implement the {interface} interface.', [
                'class'     => get_class($identity),
                'interface' => '\nineinchnick\usr\components\PasswordHistoryIdentityInterface'
            ]));
        }
        $lastUsed = $identity->getPasswordDate();
        $lastUsedDate = new DateTime($lastUsed);
        $today = new DateTime();
        if ($lastUsed === null || $today->diff($lastUsedDate)->days >= $this->passwordTimeout) {
            if ($lastUsed === null) {
                $this->owner->addError(
                    'password',
                    Yii::t('usr', 'This is the first time you login. Current password needs to be changed.')
                );
            } else {
                $this->owner->addError(
                    'password',
                    Yii::t('usr', 'Current password has been used too long and needs to be changed.')
                );
            }
            $this->owner->scenario = 'reset';

            return false;
        }

        return true;
    }
}
