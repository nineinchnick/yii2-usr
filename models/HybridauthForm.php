<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * HybridauthForm class.
 * HybridauthForm is the data structure for keeping
 * Hybridauth form data. It is used by the 'login' action of 'HybridauthController'.
 */
class HybridauthForm extends BaseUsrForm
{
    /**
     * @var string provider name selected from the list of available providers
     */
    public $provider;
    /**
     * @var string user identifier
     */
    public $openid_identifier;

    /**
     * @var array @see \nineinchnick\usr\Module::$hybridauthProviders
     */
    protected $_validProviders = [];
    protected $_hybridAuth;
    protected $_hybridAuthAdapter;
    /**
     * @var IdentityInterface cached object returned by @see getIdentity()
     */
    protected $_identity;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            [['provider', 'openid_identifier'], 'filter', 'filter'=>'trim'],
            // can't filter this because it's displayed to the user
            //['provider', 'filter', 'filter'=>'strtolower'],
            ['provider', 'required'],
            ['provider', 'validProvider'],
            ['openid_identifier', 'required', 'on'=>'openid'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        foreach ($this->_validProviders as $provider=>$options) {
            if (!isset($scenarios[$provider])) {
                $scenarios[$provider] = $scenarios[self::DEFAULT_SCENARIO];
            }
        }

        return $scenarios;
    }

    public function validProvider($attribute, $params)
    {
        $provider = strtolower($this->$attribute);

        return isset($this->_validProviders[$provider]) && $this->_validProviders[$provider];
    }

    /**
     * @param array $providers list of valid providers
     */
    public function setValidProviders($providers)
    {
        $this->_validProviders = [];
        foreach ($providers as $provider=>$options) {
            $this->_validProviders[strtolower($provider)] = !isset($options['enabled']) || $options['enabled'];
        }

        return $this;
    }

    public function setHybridAuth($hybridAuth)
    {
        $this->_hybridAuth = $hybridAuth;

        return $this;
    }

    public function getHybridAuthAdapter()
    {
        return $this->_hybridAuthAdapter;
    }

    public function getIdentity()
    {
        return $this->_identity;
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return [
            'provider'			=> Yii::t('usr','Provider'),
            'openid_identifier'	=> Yii::t('usr','OpenID Identifier'),
        ];
    }

    public function requiresFilling()
    {
        if (strtolower($this->provider) == 'openid' && empty($this->openid_identifier))
            return true;

        return false;
    }

    public function loggedInRemotely()
    {
        return ($adapter=$this->getHybridAuthAdapter()) !== null && $adapter->isUserConnected();
    }

    public function login()
    {
        $identityClass = Yii::$app->user->identityClass;
        $fakeIdentity = new $identityClass;
        if (!($fakeIdentity instanceof \nineinchnick\usr\components\HybridauthIdentityInterface))
            throw new \yii\base\Exception(Yii::t('usr','The {class} class must implement the {interface} interface.',['class'=>get_class($fakeIdentity),'interface'=>'\nineinchnick\usr\components\HybridauthIdentityInterface']));

        $params = $this->getAttributes();
        unset($params['provider']);
        $this->_hybridAuthAdapter = $this->_hybridAuth->authenticate(strtolower($this->provider), $params);

        if ($this->_hybridAuthAdapter->isUserConnected()) {
            $profile = $this->_hybridAuthAdapter->getUserProfile();
            if (($this->_identity=$identityClass::findByProvider(strtolower($this->provider), $profile->identifier)) !== null) {
                return Yii::$app->user->login($this->_identity,0);
            }
        }

        return false;
    }

    public function associate($user_id)
    {
        $identityClass = Yii::$app->user->identityClass;
        $identity = $identityClass::findIdentity($user_id);
        if ($identity === null) {
            return false;
        }
        if (!($identity instanceof \nineinchnick\usr\components\HybridauthIdentityInterface)) {
            throw new \yii\base\Exception(Yii::t('usr','The {class} class must implement the {interface} interface.',['class'=>get_class($identity),'interface'=>'\nineinchnick\usr\components\HybridauthIdentityInterface']));
        }
        $profile = $this->_hybridAuthAdapter->getUserProfile();
        if ($identity instanceof PictureIdentityInterface && !empty($profile->photoURL)) {
            $picture = $identity->getPictureUrl();
            if ($picture['url'] != $profile->photoURL) {
                $path = tempnam(sys_get_temp_dir(), 'external_profile_picture_');
                if (copy($profile->photoURL, $path)) {
                    $uploadedFile = new yii\web\UploadedFile(['name'=>basename($path), 'tempName'=>$path, 'type'=>yii\helpers\FileHelper::getMimeType($path), 'size'=>filesize($path), 'error'=>UPLOAD_ERR_OK]);
                    $identity->removePicture();
                    $identity->savePicture($uploadedFile);
                }
            }
        }

        return $identity->addRemoteIdentity(strtolower($this->provider), $profile->identifier);
    }
}
