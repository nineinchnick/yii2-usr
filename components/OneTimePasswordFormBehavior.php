<?php
/**
 * OneTimePasswordFormBehavior class file.
 *
 * @author Jan Was <janek.jan@gmail.com>
 */

namespace nineinchnick\usr\components;

use Yii;
use nineinchnick\usr\Module;

/**
 * OneTimePasswordFormBehavior adds one time password validation to a login form model component.
 *
 * @property \Google\Authenticator\GoogleAuthenticator authenticator If null, set to a new instance of GoogleAuthenticator class.
 * @property string mode If set to OneTimePasswordFormBehavior::OTP_TIME or OneTimePasswordFormBehavior::OTP_COUNTER, two step authentication is enabled using one time passwords.
 *                       Time mode uses codes generated using current time and requires the user to use an external application, like Google Authenticator on Android.
 *                       Counter mode uses codes generated using a sequence and sends them to user's email.
 * @property boolean required Should the user be allowed to log in even if a secret hasn't been generated yet (is null).
 *                            This only makes sense when mode is 'counter', secrets are generated when registering users and a code is sent via email.
 * @property integer timeout Number of seconds for how long is the last verified code valid.
 * @property \yii\base\Model $owner The owner model that this behavior is attached to.
 *
 * @author Jan Was <janek.jan@gmail.com>
 */
class OneTimePasswordFormBehavior extends FormModelBehavior
{
    const OTP_SECRET_PREFIX = 'nineinchnick.usr.Module.oneTimePassword.';
    const OTP_COOKIE = 'otp';
    const OTP_NONE = 'none';
    const OTP_TIME = 'time';
    const OTP_COUNTER = 'counter';

    /**
     * @var string One time password as a token entered by the user.
     */
    public $oneTimePassword;

    /**
     * @var \Google\Authenticator\GoogleAuthenticator If null, set to a new instance of GoogleAuthenticator class.
     */
    public $authenticator;
    /**
     * @var string If set to OneTimePasswordFormBehavior::OTP_TIME or OneTimePasswordFormBehavior::OTP_COUNTER, two step authentication is enabled using one time passwords.
     *             Time mode uses codes generated using current time and requires the user to use an external application, like Google Authenticator on Android.
     *             Counter mode uses codes generated using a sequence and sends them to user's email.
     */
    public $mode;
    /**
     * @var boolean Should the user be allowed to log in even if a secret hasn't been generated yet (is null).
     *              This only makes sense when mode is 'counter', secrets are generated when registering users and a code is sent via email.
     */
    public $required;
    /**
     * @var integer Number of seconds for how long is the last verified code valid.
     */
    public $timeout;

    private $_oneTimePasswordConfig = [
        'secret' => null,
        'previousCode' => null,
        'previousCounter' => null,
    ];

    private $_controller;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return array_merge(parent::events(), [
            \yii\base\Model::EVENT_AFTER_VALIDATE => 'afterValidate',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function filterRules($rules = [])
    {
        $behaviorRules = [
            ['oneTimePassword', 'filter', 'filter' => 'trim', 'on' => 'verifyOTP'],
            ['oneTimePassword', 'default', 'on' => 'verifyOTP'],
            ['oneTimePassword', 'required', 'on' => 'verifyOTP'],
            ['oneTimePassword', 'validOneTimePassword', 'skipOnEmpty' => false, 'except' => 'auth'],
        ];

        return array_merge($rules, $this->applyRuleOptions($behaviorRules));
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'oneTimePassword' => Yii::t('usr', 'One Time Password'),
        ];
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function setController($value)
    {
        $this->_controller = $value;
    }

    public function getOneTimePasswordConfig()
    {
        return $this->_oneTimePasswordConfig;
    }

    public static function getDefaultAuthenticator()
    {
        return new \Google\Authenticator\GoogleAuthenticator();
    }

    public function setOneTimePasswordConfig(array $config)
    {
        foreach ($config as $key => $value) {
            if ($this->_oneTimePasswordConfig[$key] === null) {
                $this->_oneTimePasswordConfig[$key] = $value;
            }
        }

        return $this;
    }

    protected function loadOneTimePasswordConfig()
    {
        $identity = $this->owner->getIdentity();
        if (!($identity instanceof \nineinchnick\usr\components\OneTimePasswordIdentityInterface)) {
            throw new \yii\base\Exception(Yii::t('usr', 'The {class} class must implement the {interface} interface.', ['class' => get_class($identity), 'interface' => '\nineinchnick\usr\components\OneTimePasswordIdentityInterface']));
        }
        list($previousCode, $previousCounter) = $identity->getOneTimePassword();
        $this->setOneTimePasswordConfig([
            'secret' => $identity->getOneTimePasswordSecret(),
            'previousCode' => $previousCode,
            'previousCounter' => $previousCounter,
        ]);
        if ($this->authenticator === null) {
            $this->authenticator = self::getDefaultAuthenticator();
        }

        return $this;
    }

    public function getOTP($key)
    {
        if ($this->_oneTimePasswordConfig[$key] === null) {
            $this->loadOneTimePasswordConfig();
        }

        return $this->_oneTimePasswordConfig[$key];
    }

    public function getNewCode()
    {
        $this->loadOneTimePasswordConfig();
        // extracts: $secret, $previousCode, $previousCounter
        extract($this->_oneTimePasswordConfig);

        return $this->authenticator->getCode($secret, $this->mode == OneTimePasswordFormBehavior::OTP_TIME ? null : $previousCounter);
    }

    public function validOneTimePassword($attribute, $params)
    {
        if ($this->owner->hasErrors()) {
            return false;
        }
        $this->loadOneTimePasswordConfig();
        // extracts: $secret, $previousCode, $previousCounter
        extract($this->_oneTimePasswordConfig);

        if (($this->mode !== OneTimePasswordFormBehavior::OTP_TIME && $this->mode !== OneTimePasswordFormBehavior::OTP_COUNTER) || (!$this->required && $secret === null)) {
            return true;
        }
        if ($this->required && $secret === null) {
            // generate and save a new secret only if required to do so, in other cases user must verify that the secret works
            $secret = $this->_oneTimePasswordConfig['secret'] = $this->authenticator->generateSecret();
            $this->owner->getIdentity()->setOneTimePasswordSecret($secret);
        }

        if ($this->isValidOTPCookie(Yii::$app->request->cookies->get(OneTimePasswordFormBehavior::OTP_COOKIE), $this->owner->username, $secret, $this->timeout)) {
            return true;
        }
        if (empty($this->owner->$attribute)) {
            $this->owner->addError($attribute, Yii::t('usr', 'Enter a valid one time password.'));
            $this->owner->scenario = 'verifyOTP';
            if ($this->mode === OneTimePasswordFormBehavior::OTP_COUNTER) {
                $this->_controller->sendEmail($this, 'oneTimePassword');
            }
            if (YII_DEBUG) {
                $this->oneTimePassword = $this->authenticator->getCode($secret, $this->mode === OneTimePasswordFormBehavior::OTP_TIME ? null : $previousCounter);
            }

            return false;
        }
        if ($this->mode === OneTimePasswordFormBehavior::OTP_TIME) {
            $valid = $this->authenticator->checkCode($secret, $this->owner->$attribute);
        } elseif ($this->mode === OneTimePasswordFormBehavior::OTP_COUNTER) {
            $valid = $this->authenticator->getCode($secret, $previousCounter) == $this->owner->$attribute;
        } else {
            $valid = false;
        }
        if (!$valid) {
            $this->owner->addError($attribute, Yii::t('usr', 'Entered code is invalid.'));
            $this->owner->scenario = 'verifyOTP';

            return false;
        }
        if ($this->owner->$attribute == $previousCode) {
            if ($this->mode === OneTimePasswordFormBehavior::OTP_TIME) {
                $message = Yii::t('usr', 'Please wait until next code will be generated.');
            } elseif ($this->mode === OneTimePasswordFormBehavior::OTP_COUNTER) {
                $message = Yii::t('usr', 'Please log in again to request a new code.');
            }
            $this->owner->addError($attribute, Yii::t('usr', 'Entered code has already been used.').' '.$message);
            $this->owner->scenario = 'verifyOTP';

            return false;
        }
        $this->owner->getIdentity()->setOneTimePassword($this->owner->$attribute, $this->mode === OneTimePasswordFormBehavior::OTP_TIME ? floor(time() / 30) : $previousCounter + 1);

        return true;
    }

    public function afterValidate($event)
    {
        if ($this->owner->scenario === 'auth' || $this->owner->hasErrors()) {
            return;
        }

        // extracts: $secret, $previousCode, $previousCounter
        extract($this->_oneTimePasswordConfig);

        $cookie = $this->createOTPCookie($this->owner->username, $secret, $this->timeout);
        Yii::$app->response->cookies->add($cookie);
    }

    public function createOTPCookie($username, $secret, $timeout, $time = null)
    {
        if ($time === null) {
            $time = time();
        }
        $data = ['username' => $username, 'time' => $time, 'timeout' => $timeout];
        $security = new \yii\base\Security();
        $cookie = new \yii\web\Cookie([
            'name' => OneTimePasswordFormBehavior::OTP_COOKIE,
            'value' => $time.':'.$security->hashData(serialize($data), $secret),
            'expire' => time() + ($timeout <= 0 ? 10*365*24*3600 : $timeout),
            'httpOnly' => true,
        ]);

        return $cookie;
    }

    public function isValidOTPCookie($cookie, $username, $secret, $timeout, $time = null)
    {
        if ($time === null) {
            $time = time();
        }

        if (!$cookie || empty($cookie->value) || !is_string($cookie->value)) {
            return false;
        }
        $parts = explode(":", $cookie->value, 2);
        if (count($parts) != 2) {
            return false;
        }
        list($creationTime, $hash) = $parts;
        $data = ['username' => $username, 'time' => (int) $creationTime, 'timeout' => $timeout];
        $security = new \yii\base\Security();
        $validHash = $security->hashData(serialize($data), $secret);

        return ($timeout <= 0 || $creationTime + $timeout >= $time) && $hash === $validHash;
    }
}
