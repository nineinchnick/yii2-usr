<?php

namespace nineinchnick\usr\controllers;

use Yii;

/**
 * The controller handling logging in using social sites.
 * @author Jan Was <jwas@nets.com.pl>
 */
class HybridauthController extends UsrController
{
    public function actionIndex()
    {
        return $this->redirect(['login']);
    }

    /**
     * Redirects user either to returnUrl or main page.
     */
    protected function afterLogin()
    {
        $returnUrlParts = explode('/', Yii::$app->user->returnUrl);
        if (end($returnUrlParts) == 'index.php') {
            $url = '/';
        } else {
            $url = Yii::$app->user->returnUrl;
        }

        return $this->redirect($url);
    }

    public function actionPopup($provider = null)
    {
        //! @todo port
        /** @var HybridauthForm */
        $remoteLogin = $this->module->createFormModel('HybridauthForm');

        if ($provider !== null) {
            $remoteLogin->provider = $provider;
            $remoteLogin->scenario = strtolower($remoteLogin->provider);

            if ($remoteLogin->validate()) {
                $remoteLogin->login();
            }
        }
        // if we got here that means Hybridauth did not perform a redirect,
        // either there was an error or the user is already authenticated
        $url = $this->createUrl('login', ['provider' => $provider]);
        $message = Yii::t('usr', 'Redirecting, please wait...');
        echo "<html><body onload=\"window.opener.location.href='$url';window.close();\">$message</body></html>";
        Yii::app()->end();
    }

    /**
     * Tries to log in. If no local account has been associated yet, it tries to locate a matching one
     * and asks to authenticate. A new local profile could also be created, either automatically or manually
     * if there are any form errors.
     * @param string $provider name of the remote provider
     */
    public function actionLogin($provider = null)
    {
        if ($provider !== null) {
            $_POST['HybridauthForm']['provider'] = $provider;
        }
        /** @var HybridauthForm */
        $remoteLogin = $this->module->createFormModel('HybridauthForm');
        /** @var LoginForm */
        $localLogin = $this->module->createFormModel('LoginForm', 'hybridauth');
        /** @var ProfileForm */
        $localProfile = $this->module->createFormModel('ProfileForm', 'register');
        //! @todo port
        $localProfile->detachBehavior('captcha');

        if (isset($_POST['ajax'])) {
            if ($_POST['ajax'] === 'remoteLogin-form') {
                echo CActiveForm::validate($remoteLogin);
            } elseif ($_POST['ajax'] === 'localProfile-form') {
                echo CActiveForm::validate($localProfile);
            } else {
                echo CActiveForm::validate($localLogin);
            }
            Yii::$app->end();
        }

        if (isset($_POST['HybridauthForm'])) {
            $remoteLogin->setAttributes($_POST['HybridauthForm']);
            $remoteLogin->scenario = strtolower($remoteLogin->provider);

            if ($remoteLogin->validate()) {
                if ($remoteLogin->login()) {
                    // user is already associated with remote identity and has been logged in
                    $this->afterLogin();
                } elseif (($adapter = $remoteLogin->getHybridAuthAdapter()) === null || !$adapter->isUserConnected()) {
                    Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to log in using {provider}.', ['provider' => $remoteLogin->provider]));

                    return $this->redirect('login');
                }
                if (!Yii::$app->user->isGuest) {
                    // user is already logged in and needs to be associated with remote identity
                    if (!$remoteLogin->associate(Yii::$app->user->getId())) {
                        Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to associate current user with {provider}.', ['provider' => $remoteLogin->provider]));

                        return $this->redirect('login');
                    }
                    $this->afterLogin();
                }
                if (!empty($this->module->associateByAttributes)) {
                    $userIdentityClass = $localProfile->userIdentityClass;
                    $remoteProfile = $remoteLogin->getHybridAuthAdapter()->getUserProfile();
                    $remoteProfileAttributes = $userIdentityClass::getRemoteAttributes($remoteProfile);
                    $searchAttributes = [];
                    foreach ($this->module->associateByAttributes as $name) {
                        if (isset($remoteProfileAttributes[$name])) {
                            $searchAttributes[$name] = $remoteProfileAttributes[$name];
                        }
                    }
                    $localIdentity = $userIdentityClass::find($searchAttributes);
                } else {
                    $localIdentity = false;
                }
                // first try to log in if form has been filled
                $localLogin = $this->performLocalLogin($localLogin, $remoteLogin, $localIdentity);
                // then try to register a new local profile
                if ($this->module->registrationEnabled) {
                    $localProfile = $this->registerLocalProfile($localProfile, $remoteLogin, $localIdentity);
                }
                return $this->render('associate', [
                    'remoteLogin' => $remoteLogin,
                    'localLogin' => $localLogin,
                    'localProfile' => $localProfile,
                    'localIdentity' => $localIdentity,
                ]);
            }
        }

        return $this->render('login', [
            'remoteLogin' => $remoteLogin,
            'localLogin' => $localLogin,
            'localProfile' => $localProfile,
        ]);
    }

    /**
     * This action actually removes association with a remote profile instead of logging out.
     * @param string $provider name of the remote provider
     */
    public function actionLogout($provider = null, $returnUrl = null)
    {
        /** @var ProfileForm */
        $model = $this->module->createFormModel('ProfileForm');
        // HybridauthForm creates an association using lowercase provider
        $model->getIdentity()->removeRemoteIdentity(strtolower($provider));
        $this->redirect($returnUrl !== null ? $returnUrl : Yii::app()->homeUrl);
    }

    /**
     * @param  LoginForm             $localLogin
     * @param  HybridauthForm        $remoteLogin
     * @param  boolean|IUserIdentity $localIdentity if not false, try to authenticate this identity instead
     * @return LoginForm             validated $localLogin
     */
    protected function performLocalLogin(\nineinchnick\usr\models\LoginForm $localLogin, \nineinchnick\usr\models\HybridauthForm $remoteLogin, $localIdentity = false)
    {
        if (!isset($_POST['LoginForm'])) {
            return $localLogin;
        }
        if (is_object($localIdentity)) {
            // force to authorize against the $localIdentity
            $attributes = $localIdentity->getAttributes();
            if (isset($attributes['username'])) {
                $_POST['LoginForm']['username'] = $attributes['username'];
            }
        }
        $localLogin->setAttributes($_POST['LoginForm']);
        if ($localLogin->validate() && $localLogin->login()) {
            // don't forget to associate the new profile with remote provider
            if (!$remoteLogin->associate($localLogin->getIdentity()->getId())) {
                Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to associate current user with {provider}.', ['provider' => $remoteLogin->provider]));

                return $this->redirect(['login', 'provider' => $remoteLogin->provider]);
            }

            $this->afterLogin();
        }

        return $localLogin;
    }

    protected function registerLocalProfile(\nineinchnick\usr\models\ProfileForm $localProfile, \nineinchnick\usr\models\HybridauthForm $remoteLogin, $localIdentity = false)
    {
        if (!isset($_POST['ProfileForm']) && $localIdentity === false) {
            $userIdentityClass = $localProfile->userIdentityClass;
            $remoteProfile = $remoteLogin->getHybridAuthAdapter()->getUserProfile();
            $localProfile->setAttributes($userIdentityClass::getRemoteAttributes($remoteProfile));
            $localProfile->validate();

            return $localProfile;
        }

        if ($localIdentity !== false) {
            $userIdentityClass = $localProfile->userIdentityClass;
            $remoteProfile = $remoteLogin->getHybridAuthAdapter()->getUserProfile();
            $localProfile->setAttributes($userIdentityClass::getRemoteAttributes($remoteProfile));
        }
        if (isset($_POST['ProfileForm']) && is_array($_POST['ProfileForm'])) {
            $localProfile->setAttributes($_POST['ProfileForm']);
        }

        if (!$localProfile->validate()) {
            return $localProfile;
        }

        $trx = Yii::$app->db->beginTransaction();
        if (!$localProfile->save($this->module->requireVerifiedEmail)) {
            $trx->rollback();
            Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to register a new user.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));

            return $localProfile;
        }

        $trx->commit();
        if ($this->module->requireVerifiedEmail) {
            if ($this->sendEmail($localProfile, 'verify')) {
                Yii::$app->session->setFlash('success', Yii::t('usr', 'An email containing further instructions has been sent to provided email address.'));
            } else {
                Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to send an email.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
            }
        }

        // don't forget to associate the new profile with remote provider
        if (!$remoteLogin->associate($localProfile->getIdentity()->getId())) {
            Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to associate current user with {provider}.', ['provider' => $remoteLogin->provider]));

            return $this->redirect('login');
        }

        if ($localProfile->getIdentity()->isActive()) {
            // don't use the $localProfile->login() method because there is no password set so we can't authenticate this identity
            if (Yii::$app->user->login($localProfile->getIdentity(), 0)) {
                $this->afterLogin();
            } else {
                Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to log in.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
            }
        } else {
            if (!Yii::$app->session->hasFlash('success')) {
                Yii::$app->session->setFlash('success', Yii::t('usr', 'Please wait for the account to be activated. A notification will be send to provided email address.'));
            }

            return $this->redirect(['login', 'provider' => $remoteLogin->provider]);
        }

        return $localProfile;
    }

    public function actionCallback()
    {
        \Hybrid_Endpoint::process();
    }
}
