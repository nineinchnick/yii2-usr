<?php

namespace nineinchnick\yii2-usr;

/**
 * @author Jan Was <jwas@nets.com.pl>
 */
class Module extends \yii\base\Module
{
	const OTP_SECRET_PREFIX = 'UsrModule.oneTimePassword.';
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
	 * If not null, the user identity class must implement IPasswordHistoryIdentity interface.
	 */
	public $passwordTimeout;
	/**
	 * @var array Set of rules to measure the password strength when choosing new password in the registration or recovery forms.
	 * If null, defaults to minimum 8 characters and at least one of each: lower and upper case character and a digit.
	 * @see BasePasswordForm
	 */
	public $passwordStrengthRules;
	/**
	 * @var string Class name of user identity object used to authenticate user.
	 * Must implement the IPasswordHistoryIdentity interface if passwordTimeout is set.
	 */
	public $userIdentityClass = 'CUserIdentity';
	/**
	 * @var string Class name for input form widgets.
	 */
	public $formClass = 'CActiveForm';
	/**
	 * @var string Class name for detail view widget.
	 */
	public $detailViewClass = 'zii.widgets.CDetailView';
	/**
	 * @var string CSS class for html forms.
	 */
	public $formCssClass = 'form';
	/**
	 * @var array static properties of CHtml class, such as errorSummaryCss and errorMessageCss.
	 */
	public $htmlCss;
	/**
	 * @var string CSS class prefix for flash messages. Set to 'alert alert-' if using Twitter Bootstrap.
	 */
	public $alertCssClassPrefix = 'flash-';
	/**
	 * @var string CSS class for the form submit buttons.
	 */
	public $submitButtonCssClass = '';
	/**
	 * @var array configuration for PHPMailer, values which are arrays will trigger methods for each value instead of setting properties.
	 * For a full reference, please resolve to PHPMailer documentation.
	 */
	public $mailerConfig = array(
		'SetLanguage' => array('en'),
		'SetFrom' => array('from@example.com', 'Administrator'),
		'AddReplyTo' => array('replyto@example.com','Administrator'),
		'IsMail' => array(),
		// SMTP options
		//'IsSMTP' => array(),
		//'Host' => 'localhost',
		//'Port' => 25,
		//'Username' => 'login',
		//'Password' => 'password',
		// extension properties
		'setPathViews' => array('usr.views.emails'),
		'setPathLayouts' => array('usr.views.layouts'),
	);
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
	 * @var array Available Hybridauth providers, indexed by name, defined as array('enabled'=>true|false, 'keys'=>array('id'=>string, 'key'=>string, 'secret'=>string), 'scope'=>string)
	 * @see http://hybridauth.sourceforge.net/userguide.html
	 */
	public $hybridauthProviders = array();
	/**
	 * @var string If set to UsrModule::OTP_TIME or UsrModule::OTP_COUNTER, two step authentication is enabled using one time passwords.
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
	 * @var GoogleAuthenticator set if $oneTimePasswordMode is not UsrModule::OTP_NONE
	 */
	protected $_googleAuthenticator;
	/**
	 * @var Hybrid_Auth set if $hybridauthProviders are not empty
	 */
	protected $_hybridauth;

	public function getVersion()
	{
		return '0.9.8';
	}

	public function init()
	{
		parent::init();
		$this->setImport(array(
			'usr.models.*',
			'usr.components.*',
		));
		$this->setComponents(array(
			'mailer' => array(
				'class' => 'usr.extensions.mailer.EMailer',
				'pathViews' => 'usr.views.emails',
				'pathLayouts' => 'usr.views.layouts',
			),
		), false);
		if (is_array($this->htmlCss)) {
			foreach($this->htmlCss as $name=>$value) {
				CHtml::$$name = $value;
			}
		}
		$this->setupMailer();
		if ($this->hybridauthEnabled()) {
			$hybridauthConfig = array(
				'base_url' => Yii::app()->createAbsoluteUrl('/'.$this->id.'/hybridauth/callback'),
				'providers' => $this->hybridauthProviders,
			);
			require dirname(__FILE__) . '/extensions/Hybrid/Auth.php';
			$this->_hybridauth = new Hybrid_Auth($hybridauthConfig);
		}
	}

	public function setupMailer()
	{
		$mailerConfig = array_merge(array(
			'IsHTML' => array(true),
			'CharSet' => 'UTF-8',
			'IsMail' => array(),
			'setPathViews' => array('usr.views.emails'),
			'setPathLayouts' => array('usr.views.layouts'),
		), $this->mailerConfig);
		foreach($mailerConfig as $key=>$value) {
			if (is_array($value)) {
				call_user_func_array(array($this->mailer, $key), $value);
			} else {
				$this->mailer->$key = $value;
			}
		}
	}

	public function hybridauthEnabled()
	{
		$providers = array_filter($this->hybridauthProviders, function($p){return !isset($p['enabled']) || $p['enabled'];});
		return !empty($providers);
	}

	public function getHybridAuth()
	{
		return $this->_hybridauth;
	}

	public function getGoogleAuthenticator()
	{
		if ($this->_googleAuthenticator === null) {
				require dirname(__FILE__) . '/extensions/GoogleAuthenticator.php/lib/GoogleAuthenticator.php';
			$this->_googleAuthenticator = new GoogleAuthenticator;
		}
		return $this->_googleAuthenticator;
	}

	public function createFormModel($class, $scenario='')
	{
		$form = new $class($scenario);
		$form->userIdentityClass = $this->userIdentityClass;
		if ($form instanceof BasePasswordForm) {
			$form->passwordStrengthRules = $this->passwordStrengthRules;
		}
		switch($class) {
			default:
				break;
			case 'ProfileForm':
			case 'RecoveryForm':
				if ($this->captcha !== null && CCaptcha::checkRequirements()) {
					$form->attachBehavior('captcha', array(
						'class' => 'CaptchaFormBehavior',
						'ruleOptions' => $class == 'ProfileForm' ? array('on'=>'register') : array('except'=>'reset,verify'),
					));
				}
				break;
			case 'LoginForm':
				if ($this->oneTimePasswordMode != UsrModule::OTP_NONE) {
					$form->attachBehavior('oneTimePasswordBehavior', array(
						'class' => 'OneTimePasswordFormBehavior',
						'oneTimePasswordConfig' => array(
							'authenticator' => $this->googleAuthenticator,
							'mode' => $this->oneTimePasswordMode,
							'required' => $this->oneTimePasswordRequired,
							'timeout' => $this->oneTimePasswordTimeout,
						),
						'controller' => Yii::app()->controller,
					));
				}
				if ($this->passwordTimeout !== null) {
					$form->attachBehavior('expiredPasswordBehavior', array(
						'class' => 'ExpiredPasswordBehavior',
						'passwordTimeout' => $this->passwordTimeout,
					));
				}
				break;
		}
		return $form;
	}
}
