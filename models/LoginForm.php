<?php

namespace nineinchnick\usr\models;

use Yii;
use yii\base\ModelEvent;

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'DefaultController'.
 */
class LoginForm extends BasePasswordForm
{
    /**
     * @event Event an event that is triggered before a user is logged in.
     */
    const EVENT_BEFORE_LOGIN = 'beforeLogin';
    /**
     * @event Event an event that is triggered after a user is logged in.
     */
    const EVENT_AFTER_LOGIN = 'afterLogin';

    public $username;
    public $password;
    public $rememberMe;

    /**
     * @var IdentityInterface cached object returned by @see getIdentity()
     */
    private $_identity;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        if (!isset($scenarios['reset'])) {
            $scenarios['reset'] = $scenarios[self::SCENARIO_DEFAULT];
        }
        if (!isset($scenarios['verifyOTP'])) {
            $scenarios['verifyOTP'] = $scenarios[self::SCENARIO_DEFAULT];
        }

        return $scenarios;
    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        $rules = $this->filterRules(array_merge([
            [['username', 'password'], 'trim'],
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'authenticate'],
        ], $this->rulesAddScenario(parent::rules(), 'reset')));

        return $rules;
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'username'   => Yii::t('usr', 'Username'),
            'password'   => Yii::t('usr', 'Password'),
            'rememberMe' => Yii::t('usr', 'Remember me when logging in next time'),
        ], $this->getBehaviorLabels());
    }

    /**
     * @inheritdoc
     */
    public function getIdentity()
    {
        if ($this->_identity === null) {
            $identityClass = $this->webUser->identityClass;
            if (($this->_identity = $identityClass::findByUsername($this->username)) === null) {
                $this->_identity = false;
            }
        }

        return $this->_identity;
    }

    /**
     * Authenticates the password.
     * This is the 'authenticate' validator as declared in rules().
     * @param  string  $attribute
     * @param  array   $params
     * @return boolean
     */
    public function authenticate($attribute, $params)
    {
        if ($this->hasErrors()) {
            return false;
        }
        $identity = $this->getIdentity();
        if (!$identity) {
            $this->addError($attribute, Yii::t('usr', 'Invalid username or password.'));

            return false;
        }
        $password = $this->scenario === 'reset' ? $this->newPassword : $this->password;
        if (($error = $identity->authenticate($password)) !== true) {
            list($code, $message) = $error;
            $this->addError($attribute, $message);

            return false;
        }

        return true;
    }

    /**
     * A wrapper for the passwordHasNotExpired method from ExpiredPasswordBehavior.
     * @param $attribute string
     * @param $params array
     * @return boolean
     */
    public function passwordHasNotExpired($attribute, $params)
    {
        if (($behavior = $this->getBehavior('expiredPasswordBehavior')) !== null) {
            return $behavior->passwordHasNotExpired($attribute, $params);
        }

        return true;
    }

    /**
     * A wrapper for the validOneTimePassword method from OneTimePasswordBehavior.
     * @param $attribute string
     * @param $params array
     * @return boolean
     */
    public function validOneTimePassword($attribute, $params)
    {
        if (($behavior = $this->getBehavior('oneTimePasswordBehavior')) !== null) {
            return $behavior->validOneTimePassword($attribute, $params);
        }

        return true;
    }

    /**
     * Resets user password using the new one given in the model.
     * @return boolean whether password reset was successful
     */
    public function resetPassword()
    {
        if ($this->hasErrors()) {
            return false;
        }
        $identity = $this->getIdentity();
        $trx = $identity->db->transaction !== null ? null : $identity->db->beginTransaction();
        if (!$identity || !$identity->resetPassword($this->newPassword)) {
            $this->addError('newPassword', Yii::t('usr', 'Failed to reset the password.'));
            if ($trx !== null) {
                $trx->rollback();
            }

            return false;
        }
        if ($trx !== null) {
            $trx->commit();
        }

        return true;
    }

    /**
     * Logs in the user using the given username and password in the model.
     * @param  integer $duration For how long the user will be logged in without any activity, in seconds.
     * @return mixed   boolean true whether login is successful or an error message
     */
    public function login($duration = 0)
    {
        if ($this->beforeLogin()) {
            $result = $this->webUser->login($this->getIdentity(), $this->rememberMe ? $duration : 0);
            if ($result) {
                $this->afterLogin();
            }

            return $result;
        }

        return false;
    }

    /**
     * This method is called before logging in a user.
     * The default implementation will trigger the [[EVENT_BEFORE_LOGIN]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @return boolean whether the user should continue to be logged in
     */
    protected function beforeLogin()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_LOGIN, $event);

        return $event->isValid;
    }

    /**
     * This method is called after the user is successfully logged in.
     * The default implementation will trigger the [[EVENT_AFTER_LOGIN]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     */
    protected function afterLogin()
    {
        $this->trigger(self::EVENT_AFTER_LOGIN, new ModelEvent());
    }
}
