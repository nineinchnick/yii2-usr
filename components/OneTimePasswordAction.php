<?php

namespace nineinchnick\usr\components;

use Yii;
use yii\base\Action;
use nineinchnick\usr\Module;

/**
 * Called from the updateProfile action, enables or disables one time passwords for two step authentication.
 * When enabling OTP user must verify that he is able to use them successfully.
 */
class OneTimePasswordAction extends Action
{
	public function run() {
		if (Yii::$app->user->isGuest)
			$this->controller->redirect(['login']);
		/** @var Module */
		$module = $this->controller->module;
		if ($module->oneTimePasswordRequired)
			$this->controller->redirect(['profile']);

		$model = new \nineinchnick\usr\models\OneTimePasswordForm;
		/** @var IdentityInterface */
		$identity = $model->getIdentity();
		/**
		 * Disable OTP when a secret is set.
		 */
		if ($identity->getOneTimePasswordSecret() !== null) {
			$identity->setOneTimePasswordSecret(null);
			Yii::$app->response->cookies->remove(Module::OTP_COOKIE);
			$this->controller->redirect(['profile']);
			return;
		}

		$model->setMode($module->oneTimePasswordMode)->setAuthenticator($module->googleAuthenticator);

		/**
		 * When no secret has been set yet, generate a new secret and save it in session.
		 * Do it if it hasn't been done yet.
		 */
		if (($secret=Yii::$app->session[Module::OTP_SECRET_PREFIX.'newSecret']) === null) {
			$secret = Yii::$app->session[Module::OTP_SECRET_PREFIX.'newSecret'] = $module->googleAuthenticator->generateSecret();

			$model->setSecret($secret);
			if ($module->oneTimePasswordMode === Module::OTP_COUNTER) {
				$this->controller->sendEmail($model, 'oneTimePassword');
			}
		}
		$model->setSecret($secret);

		if ($model->load($_POST)) {
			if ($model->validate()) {
				// save secret
				$identity->setOneTimePasswordSecret($secret);
				Yii::$app->session[Module::OTP_SECRET_PREFIX.'newSecret'] = null;
				// save current code as used
				$identity->setOneTimePassword($model->oneTimePassword, $module->oneTimePasswordMode === Module::OTP_TIME ? floor(time() / 30) : $model->getPreviousCounter() + 1);
				$this->controller->redirect(['profile']);
			}
		}
		if (YII_DEBUG) {
			$model->oneTimePassword = $module->googleAuthenticator->getCode($secret, $module->oneTimePasswordMode === Module::OTP_TIME ? null : $model->getPreviousCounter());
		}

		if ($module->oneTimePasswordMode === Module::OTP_TIME) {
			$hostInfo = Yii::$app->request->hostInfo;
			$url = $model->getUrl($identity->username, parse_url($hostInfo, PHP_URL_HOST), $secret);
		} else {
			$url = '';
		}

		return $this->controller->render('generateOTPSecret', ['model'=>$model, 'url'=>$url]);
	}
}
