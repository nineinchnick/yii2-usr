<?php

namespace nineinchnick\usr\components;

use Yii;
use yii\base\Action;

/**
 * Called from the updateProfile action, enables or disables one time passwords for two step authentication.
 * When enabling OTP user must verify that he is able to use them successfully.
 */
class OneTimePasswordAction extends Action
{
    /**
     * @var array Same configuration as set for @see OneTimePasswordFormBehavior.
     */
    public $configuration;

    public function run()
    {
        if (Yii::$app->user->isGuest) {
            $this->controller->redirect(['login']);
        }
        $this->configuration = array_merge([
            'authenticator' => null,
            'mode'          => null,
            'required'      => null,
            'timeout'       => null,
        ], $this->configuration);
        if ($this->configuration['required']) {
            $this->controller->redirect(['profile']);
        }

        $model = new \nineinchnick\usr\models\OneTimePasswordForm();
        /** @var IdentityInterface */
        $identity = $model->getIdentity();
        /**
         * Disable OTP when a secret is set.
         */
        if ($identity->getOneTimePasswordSecret() !== null) {
            $identity->setOneTimePasswordSecret(null);
            Yii::$app->response->cookies->remove(OneTimePasswordFormBehavior::OTP_COOKIE);

            return $this->controller->redirect(['profile']);
        }

        $model->setMode($this->configuration['mode'])->setAuthenticator($this->configuration['authenticator']);

        /**
         * When no secret has been set yet, generate a new secret and save it in session.
         * Do it if it hasn't been done yet.
         */
        if (($secret = Yii::$app->session[OneTimePasswordFormBehavior::OTP_SECRET_PREFIX.'newSecret']) === null) {
            $secret = Yii::$app->session[OneTimePasswordFormBehavior::OTP_SECRET_PREFIX.'newSecret'] = $this->configuration['authenticator']->generateSecret();

            $model->setSecret($secret);
            if ($this->configuration['mode'] === OneTimePasswordFormBehavior::OTP_COUNTER) {
                $this->controller->sendEmail($model, 'oneTimePassword');
            }
        }
        $model->setSecret($secret);

        if ($model->load($_POST)) {
            if ($model->validate()) {
                // save secret
                $identity->setOneTimePasswordSecret($secret);
                Yii::$app->session[OneTimePasswordFormBehavior::OTP_SECRET_PREFIX.'newSecret'] = null;
                // save current code as used
                $identity->setOneTimePassword($model->oneTimePassword, $this->configuration['mode'] === OneTimePasswordFormBehavior::OTP_TIME ? floor(time() / 30) : $model->getPreviousCounter() + 1);
                $this->controller->redirect('profile');
            }
        }
        if (YII_DEBUG) {
            $model->oneTimePassword = $this->configuration['authenticator']->getCode($secret, $this->configuration['mode'] === OneTimePasswordFormBehavior::OTP_TIME ? null : $model->getPreviousCounter());
        }

        if ($this->configuration['mode'] === OneTimePasswordFormBehavior::OTP_TIME) {
            $hostInfo = Yii::$app->request->hostInfo;
            $url = $model->getUrl($identity->username, parse_url($hostInfo, PHP_URL_HOST), $secret);
        } else {
            $url = '';
        }

        return $this->controller->render('generateOTPSecret', [
            'model' => $model,
            'url'   => $url,
            'mode'  => $this->configuration['mode'],
        ]);
    }
}
