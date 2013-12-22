<?php

namespace nineinchnick\usr;

use Yii;

/**
 * @author Jan Was <jwas@nets.com.pl>
 */
class Module extends \yii\base\Module
{
	const OTP_SECRET_PREFIX = 'nineinchnick.usr.Module.oneTimePassword.';
	const OTP_COOKIE = 'otp';
	const OTP_NONE = 'none';
	const OTP_TIME = 'time';
	const OTP_COUNTER = 'counter';

	/**
	 * @var boolean Is new user registration enabled.
	 */
	public $registrationEnabled = true;
	/**
	 * @var boolean Does every new user needs to verify supplied email.
	 */
	public $requireVerifiedEmail = true;
	/**
	 * @var boolean Is password recovery permitted.
	 */
	public $recoveryEnabled = true;
	/**
	 * @var integer For how long the user will be logged in without any activity, in seconds. Defaults to 3600*24*30 seconds (30 days).
	 */
	public $rememberMeDuration = 2592000;
	/**
	 * @var integer Timeout in days after which user is requred to reset his password after logging in.
	 * If not null, the user identity class must implement PasswordHistoryIdentityInterface.
	 */
	public $passwordTimeout;
	/**
	 * @var array Set of rules to measure the password strength when choosing new password in the registration or recovery forms.
	 * If null, defaults to minimum 8 characters and at least one of each: lower and upper case character and a digit.
	 * @see BasePasswordForm
	 */
	public $passwordStrengthRules;
	/**
	 * @var string CSS class for html forms.
	 */
	public $formCssClass = 'well';
	/**
	 * @var boolean If true a link for generating passwords will be rendered under new password field.
	 */
	public $dicewareEnabled = true;
	/**
	 * @var integer Number of words in password generated using the diceware component.
	 */
	public $dicewareLength = 4;
	/**
	 * @var boolean Should an extra digit be added in password generated using the diceware component.
	 */
	public $dicewareExtraDigit = true;
	/**
	 * @var integer Should an extra random character be added in password generated using the diceware component.
	 */
	public $dicewareExtraChar = false;
	/**
	 * @var array Available Hybridauth providers, indexed by name, defined as ['enabled'=>true|false, 'keys'=>['id'=>string, 'key'=>string, 'secret'=>string], 'scope'=>string]
	 * @see http://hybridauth.sourceforge.net/userguide.html
	 */
	public $hybridauthProviders = [];
	/**
	 * @var string If set to nineinchnick\usr\Module::OTP_TIME or nineinchnick\usr\Module::OTP_COUNTER, two step authentication is enabled using one time passwords.
	 * Time mode uses codes generated using current time and requires the user to use an external application, like Google Authenticator on Android.
	 * Counter mode uses codes generated using a sequence and sends them to user's email.
	 */
	public $oneTimePasswordMode = self::OTP_NONE;
	/**
	 * @var integer Number of seconds for how long is the last verified code valid.
	 */
	public $oneTimePasswordTimeout = -1;
	/**
	 * @var boolean Should the user be allowed to log in even if a secret hasn't been generated yet (is null).
	 * This only makes sense when mode is 'counter', secrets are generated when registering users and a code is sent via email.
	 */
	public $oneTimePasswordRequired = false;

	/**
	 * @var array If not null, CAPTCHA will be enabled on the registration and recovery form and this will be passed as arguments to the CCaptcha widget.
	 */
	public $captcha;

	/**
	 * @var GoogleAuthenticator set if $oneTimePasswordMode is not nineinchnick\usr\Module::OTP_NONE
	 */
	protected $_googleAuthenticator;
	/**
	 * @var Hybrid_Auth set if $hybridauthProviders are not empty
	 */
	protected $_hybridauth;

	/**
	 * @inheritdoc
	 */
	public function getVersion()
	{
		return '0.9.9';
	}

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		\Yii::setAlias('@usr', dirname(__FILE__));
		\Yii::$app->i18n->translations['usr'] = [
			'class' => 'yii\i18n\PhpMessageSource',
			'sourceLanguage' => 'en-US',
			'basePath' => '@usr/messages',
		];
		\Yii::$app->mail->viewPath = '@usr/views/emails';
		if ($this->hybridauthEnabled()) {
			$hybridauthConfig = [
				'base_url' => Yii::$app->getUrlManager()->createAbsoluteUrl('/'.$this->id.'/hybridauth/callback'),
				'providers' => $this->hybridauthProviders,
			];
			$this->_hybridauth = new \Hybrid_Auth($hybridauthConfig);
		}
	}

	/**
	 * Checks if any Hybridauth provider has been configured.
	 * @return boolean
	 */
	public function hybridauthEnabled()
	{
		$providers = array_filter($this->hybridauthProviders, function($p){return !isset($p['enabled']) || $p['enabled'];});
		return !empty($providers);
	}

	/**
	 * Gets the Hybridauth object
	 * @return Hybrid_Auth 
	 */
	public function getHybridAuth()
	{
		return $this->_hybridauth;
	}

	/**
	 * Gets the GoogleAuthenticator object
	 * @return GoogleAuthenticator
	 */
	public function getGoogleAuthenticator()
	{
		if ($this->_googleAuthenticator === null) {
			$this->_googleAuthenticator = new \Google\Authenticator\GoogleAuthenticator;
		}
		return $this->_googleAuthenticator;
	}

	/**
	 * A factory to create pre-configured form models. Only model class names from the nineinchnick\usr\models namespace are allowed.
	 * Sets scenario, password strength rules for models extending BasePasswordForm and attaches behaviors.
	 *
	 * @param string $class without the namespace
	 * @param string $scenario
	 * @return Model
	 */
	public function createFormModel($class, $scenario=null)
	{
		$namespacedClass = "\\nineinchnick\\usr\\models\\{$class}";
		/** @var Model */
		$form = new $namespacedClass;
		if ($scenario !== null)
			$form->setScenario($scenario);
		if ($form instanceof BasePasswordForm) {
			$form->passwordStrengthRules = $this->passwordStrengthRules;
		}
		switch($class) {
			default:
				break;
			case 'ProfileForm':
			case 'RecoveryForm':
				if ($this->captcha !== null && \yii\captcha\Captcha::checkRequirements()) {
					$form->attachBehavior('captcha', [
						'class' => 'nineinchnick\usr\components\CaptchaFormBehavior',
						'ruleOptions' => $class == 'ProfileForm' ? ['on'=>'register'] : ['except'=>['reset','verify']],
					]);
				}
				break;
			case 'LoginForm':
				if ($this->oneTimePasswordMode != self::OTP_NONE) {
					$form->attachBehavior('oneTimePasswordBehavior', [
						'class' => 'nineinchnick\usr\components\OneTimePasswordFormBehavior',
						'oneTimePasswordConfig' => [
							'authenticator' => $this->googleAuthenticator,
							'mode' => $this->oneTimePasswordMode,
							'required' => $this->oneTimePasswordRequired,
							'timeout' => $this->oneTimePasswordTimeout,
						],
						'controller' => Yii::$app->controller,
					]);
				}
				if ($this->passwordTimeout !== null) {
					$form->attachBehavior('expiredPasswordBehavior', [
						'class' => 'nineinchnick\usr\components\ExpiredPasswordBehavior',
						'passwordTimeout' => $this->passwordTimeout,
					]);
				}
				break;
		}
		return $form;
	}

	/**
	 * Modify createController() to handle routes in the default controller
	 *
	 * This is a temporary hack until they add in url management via modules
	 * @link https://github.com/yiisoft/yii2/issues/810
	 * @link http://www.yiiframework.com/forum/index.php/topic/21884-module-and-url-management/
	 *
	 * "usr" and "usr/default" work like normal
	 * "usr/xxx" gets changed to "usr/default/xxx"
	 *
	 * @inheritdoc
	 */
	public function createController($route) {
		// check valid routes
		$validRoutes = [$this->defaultRoute, "hybridauth"];
		$isValidRoute = false;
		foreach ($validRoutes as $validRoute) {
			if (strpos($route, $validRoute) === 0) {
				$isValidRoute = true;
				break;
			}
		}

		if (!empty($route) && !$isValidRoute) {
			$route = $this->defaultRoute.'/'.$route;
		}
		return parent::createController($route);
	}
}
