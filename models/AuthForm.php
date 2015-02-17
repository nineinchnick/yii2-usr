<?php

namespace nineinchnick\usr\models;

use Yii;
use \nineinchnick\usr\components\AuthClientIdentityInterface;
use \nineinchnick\usr\components\PictureIdentityInterface;
use \yii\authclient\OpenId;
use \yii\authclient\OAuth1;
use \yii\authclient\OAuth2;

/**
 * AuthForm class.
 * AuthForm is the data structure for keeping
 * Auth form data. It is used by the 'login' action of 'AuthController'.
 */
class AuthForm extends BaseUsrForm
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
     * @var array @see \nineinchnick\usr\Module::$authProviders
     */
    protected $_validProviders = [];
    protected $_authClient;
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
            [['provider', 'openid_identifier'], 'trim'],
            // can't filter this because it's displayed to the user
            //['provider', 'filter', 'filter'=>'strtolower'],
            ['provider', 'required'],
            ['provider', 'validProvider'],
            ['openid_identifier', 'required', 'on' => 'openid'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        foreach ($this->_validProviders as $provider => $enabled) {
            if (!isset($scenarios[$provider])) {
                $scenarios[$provider] = $scenarios[self::SCENARIO_DEFAULT];
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
        foreach ($providers as $provider) {
            $this->_validProviders[strtolower($provider)] = true;
        }

        return $this;
    }

    public function setAuthClient($authClient)
    {
        $this->_authClient = $authClient;

        return $this;
    }

    public function getAuthClient()
    {
        if ($this->_authClient === null && $this->provider !== null) {
            $collection = Yii::$app->get('authClientCollection');
            if (!$collection->hasClient($this->provider)) {
                throw new NotFoundHttpException("Unknown auth client '{$this->provider}'");
            }
            $this->_authClient = $collection->getClient($this->provider);
        }

        return $this->_authClient;
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
            'provider'            => Yii::t('usr', 'Provider'),
            'openid_identifier'    => Yii::t('usr', 'OpenID Identifier'),
        ];
    }

    public function requiresFilling()
    {
        if (strtolower($this->provider) == 'openid' && empty($this->openid_identifier)) {
            return true;
        }

        return false;
    }

    public function loggedInRemotely()
    {
        if (($client = $this->getAuthClient()) === null) {
            return false;
        }
        if ($client instanceof OpenId) {
            return $client->validate();
        }
        if ($client instanceof OAuth1 || $client instanceof OAuth2) {
            return ($accessToken = $client->getAccessToken()) !== null && is_object($accessToken) && $accessToken->getIsValid();
        }

        return false;
    }

    public function login()
    {
        $identityClass = $this->webUser->identityClass;
        $fakeIdentity = new $identityClass();
        if (!($fakeIdentity instanceof AuthClientIdentityInterface)) {
            throw new \yii\base\Exception(Yii::t('usr', 'The {class} class must implement the {interface} interface.', [
                'class' => get_class($fakeIdentity),
                'interface' => '\nineinchnick\usr\components\AuthClientIdentityInterface',
            ]));
        }

        if ($this->loggedInRemotely()) {
            $profile = $this->_authClient->getUserAttributes();
            if (($this->_identity = $identityClass::findByProvider(strtolower($this->provider), $profile['id'])) !== null) {
                return $this->webUser->login($this->_identity, 0);
            }
        }

        return false;
    }

    public function associate($user_id)
    {
        $identityClass = $this->webUser->identityClass;
        $identity = $identityClass::findIdentity($user_id);
        if ($identity === null) {
            return false;
        }
        if (!($identity instanceof AuthClientIdentityInterface)) {
            throw new \yii\base\Exception(Yii::t('usr', 'The {class} class must implement the {interface} interface.', [
                'class' => get_class($identity),
                'interface' => '\nineinchnick\usr\components\AuthClientIdentityInterface',
            ]));
        }
        $profile = $this->_authClient->getUserAttributes();
        if ($identity instanceof PictureIdentityInterface && !empty($profile['photoURL'])) {
            $picture = $identity->getPictureUrl();
            if ($picture['url'] != $profile['photoURL']) {
                $path = tempnam(sys_get_temp_dir(), 'external_profile_picture_');
                if (copy($profile['photoURL'], $path)) {
                    $uploadedFile = new \yii\web\UploadedFile([
                        'name' => basename($path),
                        'tempName' => $path,
                        'type' => \yii\helpers\FileHelper::getMimeType($path),
                        'size' => filesize($path),
                        'error' => UPLOAD_ERR_OK,
                    ]);
                    $identity->removePicture();
                    $identity->savePicture($uploadedFile);
                }
            }
        }

        return $identity->addRemoteIdentity(strtolower($this->provider), $profile['id']);
    }
}
