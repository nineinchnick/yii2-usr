<?php

namespace nineinchnick\usr;

use Yii;
use yii\base\Model;

/**
 * @author Jan Was <jwas@nets.com.pl>
 */
class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
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
     * @var integer For how long the user will be logged in without any activity, in seconds.
     * Defaults to 3600*24*30 seconds (30 days).
     */
    public $rememberMeDuration = 2592000;
    /**
     * @var array Set of rules to measure the password strength when choosing new password
     * in the registration or recovery forms.
     * Rules should NOT include attribute name, it will be added when they are used.
     * If null, defaults to minimum 8 characters and at least one of each: lower and upper case character and a digit.
     * @see BasePasswordForm
     */
    public $passwordStrengthRules;
    /**
     * @var array Set of rules that restricts what images can be uploaded as user picture.
     * If null, picture upload is disabled.
     * Rules should NOT include attribute name, it will be added when they are used.
     * This should probably include a 'file' validator, like in the following example:
     * [
     *     ['file', 'skipOnEmpty' => true, 'extensions'=>'jpg, gif, png', 'maxSize'=>2*1024*1024, 'maxFiles' => 1],
     * ],
     * @see yii\validators\FileValidator
     */
    public $pictureUploadRules;
    /**
     * @var string CSS class for html forms.
     */
    public $formCssClass = 'well';
    /**
     * @var string Class name for detail view widget.
     */
    public $detailViewClass = '\yii\widgets\DetailView';
    /**
     * @var string CSS class for the form submit buttons.
     */
    public $submitButtonCssClass = 'btn btn-primary';
    /**
     * @var boolean If true a link for generating passwords will be rendered under new password field.
     */
    public $dicewareEnabled = true;
    /**
     * @var integer Number of words in password generated using the diceware component.
     */
    public $dicewareLength = 6;
    /**
     * @var boolean Should an extra digit be added in password generated using the diceware component.
     */
    public $dicewareExtraDigit = true;
    /**
     * @var integer Should an extra random character be added in password generated using the diceware component.
     */
    public $dicewareExtraChar = false;
    /**
     * @var array list of identity attribute names that should be passed to UserIdentity::find() to find
     * a local identity matching a remote one.
     * If one is found, user must authorize to associate it. If none has been found, a new local identity
     * is automatically registered.
     * If the attribute list is empty a full pre-filled registration and login forms are displayed.
     */
    public $associateByAttributes = ['email'];

    /**
     * @var array If not null, CAPTCHA will be enabled on the registration and recovery form and this will be
     * passed as arguments to the Captcha widget.
     * Remember to include the 'captchaAction'=>'/usr/default/captcha' property. Adjust the module id.
     */
    public $captcha;
    /**
     * @var array Extra behaviors to attach to the profile form. If the view/update views are overridden in a theme
     * this can be used to display/update extra profile fields. @see FormModelBehavior
     */
    public $profileFormBehaviors;

    /**
     * @var array Extra behaviors to attach to the login form. If the views are overridden in a theme
     * this can be used to placed extra logic. @see FormModelBehavior
     */
    public $loginFormBehaviors;

    /**
     * @var array View params used in different LoginForm model scenarios.
     * View name can be changed by setting the 'view' key.
     */
    public $scenarioViews = [
        'reset' => ['view' => 'reset'],
        'verifyOTP' => ['view' => 'verifyOTP'],
    ];

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap[$this->id] = 'nineinchnick\usr\commands\UsrController';
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        \Yii::setAlias('@usr', dirname(__FILE__));
        \Yii::$app->i18n->translations['manager'] = \Yii::$app->i18n->translations['auth'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath' => '@usr/messages',
        ];
        \Yii::$app->i18n->translations['usr'] = \Yii::$app->i18n->translations['manager'];

        if (\Yii::$app->mailer !== null) {
            \Yii::$app->mailer->viewPath = '@usr/views/emails';
        }
    }

    /**
     * A factory to create pre-configured form models. Only model class names
     * from the nineinchnick\usr\models namespace are allowed.
     * Sets scenario, password strength rules for models extending BasePasswordForm and attaches behaviors.
     *
     * @param  string $class    without the namespace
     * @param  string $scenario
     * @return Model
     */
    public function createFormModel($class, $scenario = null)
    {
        $namespacedClass = "\\nineinchnick\\usr\\models\\{$class}";
        /** @var Model */
        $form = new $namespacedClass();
        if ($scenario !== null) {
            $form->setScenario($scenario);
        }
        if ($form instanceof \nineinchnick\usr\models\BaseUsrForm) {
            $form->webUser = Yii::$app->user;
        }
        if ($form instanceof \nineinchnick\usr\models\BasePasswordForm) {
            $form->passwordStrengthRules = $this->passwordStrengthRules;
        }
        switch ($class) {
            default:
                break;
            case 'ProfileForm':
                $form->pictureUploadRules = $this->pictureUploadRules;
                if (!empty($this->profileFormBehaviors)) {
                    foreach ($this->profileFormBehaviors as $name => $config) {
                        $form->attachBehavior($name, $config);
                    }
                }
                // no break
            case 'RecoveryForm':
                if ($this->captcha !== null && \yii\captcha\Captcha::checkRequirements()) {
                    $form->attachBehavior('captcha', [
                        'class' => 'nineinchnick\usr\components\CaptchaFormBehavior',
                        'ruleOptions' => $class == 'ProfileForm'
                            ? ['on' => 'register']
                            : ['except' => ['reset', 'verify']],
                    ]);
                }
                break;
            case 'LoginForm':
                if ($this->loginFormBehaviors !== null && is_array($this->loginFormBehaviors)) {
                    foreach ($this->loginFormBehaviors as $name => $config) {
                        $form->attachBehavior($name, $config);
                    }
                }
                break;
            case 'AuthForm':
                $form->setValidProviders(array_keys(Yii::$app->get('authClientCollection')->clients));
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
    public function createController($route)
    {
        if (\Yii::$app instanceof \yii\console\Application) {
            return parent::createController($route);
        }
        // check valid routes
        $validRoutes = [$this->defaultRoute, 'auth', 'manager'];
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
