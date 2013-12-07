<?php

namespace nineinchnick\usr\controllers;

use Yii;
use yii\web\HttpException;

/**
 * The default controller providing all basic actions.
 * @author Jan Was <jwas@nets.com.pl>
 */
class DefaultController extends UsrController
{
	public function actions()
	{
		$actions = [];
		if ($this->module->captcha !== null) {
			// captcha action renders the CAPTCHA image displayed on the register and recovery page
			$actions['captcha'] = [
				'class'=>'\yii\captcha\CaptchaAction',
				'backColor'=>0xFFFFFF,
				'testLimit'=>0,
			];
		}
		if ($this->module->dicewareEnabled) {
			// DicewareAction generates a random passphrase
			$actions['password'] = [
				'class'=>'\nineinchnick\usr\components\DicewareAction',
				'length'=>$this->module->dicewareLength,
				'extraDigit'=>$this->module->dicewareExtraDigit,
				'extraChar'=>$this->module->dicewareExtraChar,
			];
		}
		if ($this->module->oneTimePasswordMode != self::OTP_NONE) {
			// OneTimePaswordAction allows toggling two step auth in user profile
			$actions['toggleOneTimePassword'] = [
				'class'=>'\nineinchnick\usr\components\OneTimePaswordAction',
			];
		}
		return $actions;
	}

	/**
	 * Users are redirected to their profile if logged in and to login page otherwise.
	 */
	public function actionIndex()
	{
		if (Yii::$app->user->isGuest)
			$this->redirect(['login']);
		else
			$this->redirect(['profile']);
	}

	/**
	 * Redirects user either to returnUrl or main page.
	 */ 
	protected function afterLogin()
	{
		$returnUrlParts = explode('/',Yii::$app->user->returnUrl);
		if(end($returnUrlParts)=='index.php'){
			$url = '/';
		}else{
			$url = Yii::$app->user->returnUrl;
		}
		$this->redirect($url);
	}

	/**
	 * Performs user login, expired password reset or one time password verification.
	 * @param string $scenario
	 * @return string
	 */
	public function actionLogin($scenario = null)
	{
		if (!Yii::$app->user->isGuest)
			return $this->goBack();

		/** @var LoginForm */
		$model = $this->module->createFormModel('LoginForm');
		if ($scenario !== null && in_array($scenario, ['reset', 'verifyOTP'])) {
			$model->scenario = $scenario;
		}

		if ($model->load($_POST)) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
				return \yii\widgets\ActiveForm::validate($model);
			}
			if($model->validate()) {
				if (($model->scenario !== 'reset' || $model->resetPassword()) && $model->login($this->module->rememberMeDuration)) {
					$this->afterLogin();
				} else {
					Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to change password or log in using new password.'));
				}
			}
		}
		switch($model->scenario) {
		default: $view = 'login'; break;
		case 'reset': $view = 'reset'; break;
		case 'verifyOTP': $view = 'verifyOTP'; break;
		}
		return $this->render($view, ['model'=>$model]);
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::$app->user->logout();
		return $this->goHome();
	}

	/**
	 * Processes a request for password recovery email or resetting the password. 
	 * @return string
	 */
	public function actionRecovery()
	{
		if (!$this->module->recoveryEnabled) {
			throw new HttpException(403,Yii::t('usr', 'Password recovery has not been enabled.'));
		}
		if (!Yii::$app->user->isGuest)
			$this->redirect(Yii::$app->user->returnUrl);

		/** @var RecoveryForm */
		$model = $this->module->createFormModel('RecoveryForm');
		if (isset($_GET['activationKey'])) {
			$model->scenario = 'reset';
			$model->setAttributes($_GET);
		}
		if ($model->load($_POST)) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
				return \yii\widgets\ActiveForm::validate($model);
			}
			/**
			 * If the activation key is missing that means the user is requesting a recovery email.
			 */
			if ($model->activationKey !== null)
				$model->scenario = 'reset';
			if($model->validate()) {
				if ($model->scenario !== 'reset') {
					if ($this->sendEmail($model, 'recovery')) {
						Yii::$app->session->setFlash('success', Yii::t('usr', 'An email containing further instructions has been sent to email associated with specified user account.'));
					} else {
						Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to send an email.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
					}
				} else {
					$model->getIdentity()->verifyEmail();
					if ($model->resetPassword() && $model->login()) {
						$this->afterLogin();
					} else {
						Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to change password or log in using new password.'));
					}
				}
				$this->redirect(['recovery']);
			}
		}
		return $this->render('recovery', ['model'=>$model]);
	}

	/**
	 * Processes email verification.
	 * @return string
	 */
	public function actionVerify()
	{
		/** @var RecoveryForm */
		$model = $this->module->createFormModel('RecoveryForm', 'verify');
		if (!isset($_GET['activationKey'])) {
			throw new HttpException(400,Yii::t('usr', 'Activation key is missing.'));
		}
		$model->setAttributes($_GET);
		if($model->validate() && $model->getIdentity()->verifyEmail()) {
			Yii::$app->session->setFlash('success', Yii::t('usr', 'Your email address has been successfully verified.'));
		} else {
			Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to verify your email address.'));
		}
		$this->redirect([Yii::$app->user->isGuest ? 'login' : 'profile']);
	}

	/**
	 * Performs user sign-up.
	 * @return string
	 */
	public function actionRegister()
	{
		if (!$this->module->registrationEnabled) {
			throw new HttpException(403,Yii::t('usr', 'Registration has not been enabled.'));
		}
		if (!Yii::$app->user->isGuest)
			$this->redirect(['profile']);

		/** @var ProfileForm */
		$model = $this->module->createFormModel('ProfileForm', 'register');
		/** @var PasswordForm */
		$passwordForm = $this->module->createFormModel('PasswordForm', 'register');

		if($model->load($_POST)) {
			$passwordForm->load($_POST);
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
				return \yii\widgets\ActiveForm::validate($model, $passwordForm);
			}
			if ($model->validate() && $passwordForm->validate()) {
				$trx = Yii::$app->db->beginTransaction();
				if (!$model->save() || !$passwordForm->resetPassword($model->getIdentity())) {
					$trx->rollback();
					Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to register a new user.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
				} else {
					$trx->commit();
					if ($this->module->requireVerifiedEmail) {
						if ($this->sendEmail($model, 'verify')) {
							Yii::$app->session->setFlash('success', Yii::t('usr', 'An email containing further instructions has been sent to provided email address.'));
						} else {
							Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to send an email.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
						}
					}
					if ($model->getIdentity()->isActive()) {
						if ($model->login()) {
							$this->afterLogin();
						} else {
							Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to log in.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
						}
					} else {
						if (!Yii::$app->user->hasFlash('success'))
							Yii::$app->session->setFlash('success', Yii::t('usr', 'Please wait for the account to be activated. A notification will be send to provided email address.'));
						$this->redirect(['login']);
					}
				}
			}
		}
		return $this->render('updateProfile', ['model'=>$model, 'passwordForm'=>$passwordForm]);
	}

	/**
	 * Allows users to view or update their profile.
	 * @param boolean $update
	 * @return string
	 */
	public function actionProfile($update=false)
	{
		if (Yii::$app->user->isGuest)
			$this->redirect(['login']);

		/** @var ProfileForm */
		$model = $this->module->createFormModel('ProfileForm');
		$model->setAttributes($model->getIdentity()->getIdentityAttributes());
		$loadedModel = $model->load($_POST);
		/** @var PasswordForm */
		$passwordForm = $this->module->createFormModel('PasswordForm');
		$loadedPassword = isset($_POST[$passwordForm->formName()]) && trim($_POST[$passwordForm->formName()]['newPassword']) !== '' && $passwordForm->load($_POST);

		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			$models = [];
			if ($loadedModel) {
				$models[] = $model;
			}
			if ($loadedPassword) {
				$models[] = $passwordForm;
			}
			return \yii\widgets\ActiveForm::validateMultiple($models);
		}
		$flashes = ['success'=>[], 'error'=>[]];
		/**
		 * Only try to set new password if it has been specified in the form.
		 * The current password could have been used to authorize other changes.
		 */
		if($loadedPassword) {
			if ($passwordForm->validate()) {
				if ($passwordForm->resetPassword($model->getIdentity())) {
					$flashes['success'][] = Yii::t('usr', 'Changes have been saved successfully.');
				} else {
					$flashes['error'][] = Yii::t('usr', 'Failed to change password.');
				}
			}
		}
		if($loadedModel && empty($flashes['error'])) {
			if($model->validate()) {
				$oldEmail = $model->getIdentity()->getEmail();
				if ($model->save()) {
					if ($this->module->requireVerifiedEmail && $oldEmail != $model->email) {
						if ($this->sendEmail($model, 'verify')) {
							$flashes['success'][] = Yii::t('usr', 'An email containing further instructions has been sent to provided email address.');
						} else {
							$flashes['error'][] = Yii::t('usr', 'Failed to send an email.').' '.Yii::t('usr', 'Try again or contact the site administrator.');
						}
					}
					$flashes['success'][] = Yii::t('usr', 'Changes have been saved successfully.');
					if (!empty($flashes['success']))
						Yii::$app->session->setFlash('success', implode('<br/>',$flashes['success']));
					if (!empty($flashes['error']))
						Yii::$app->session->setFlash('error', implode('<br/>',$flashes['error']));
					$this->redirect(['profile']);
				} else {
					$flashes['error'][] = Yii::t('usr', 'Failed to update profile.').' '.Yii::t('usr', 'Try again or contact the site administrator.');
				}
			}
		}
		if (!empty($flashes['success']))
			Yii::$app->session->setFlash('success', implode('<br/>',$flashes['success']));
		if (!empty($flashes['error']))
			Yii::$app->session->setFlash('error', implode('<br/>',$flashes['error']));
		if ($update) {
			return $this->render('updateProfile', ['model'=>$model, 'passwordForm'=>$passwordForm]);
		} else {
			return $this->render('viewProfile', ['model'=>$model]);
		}
	}

	/**
	 * Called from the updateProfile action, enables or disables one time passwords for two step authentication.
	 * When enabling OTP user must verify that he is able to use them successfully.
	 * @return string
	 */
	public function actionToggleOneTimePassword()
	{
		if (Yii::$app->user->isGuest)
			$this->redirect(['login']);
		if ($this->module->oneTimePasswordRequired)
			$this->redirect(['profile']);

		$model = new OneTimePasswordForm;
		$identity = $model->getIdentity();
		/**
		 * Disable OTP when a secret is set.
		 */
		if ($identity->getOneTimePasswordSecret() !== null) {
			$identity->setOneTimePasswordSecret(null);
			Yii::$app->request->cookies->remove(nineinchnick\usr\Module::OTP_COOKIE);
			$this->redirect('profile');
			return;
		}

		$model->setMode($this->module->oneTimePasswordMode)->setAuthenticator($this->module->googleAuthenticator);

		/**
		 * When no secret has been set yet, generate a new secret and save it in session.
		 * Do it if it hasn't been done yet.
		 */
		if (($secret=Yii::$app->session[nineinchnick\usr\Module::OTP_SECRET_PREFIX.'newSecret']) === null) {
			$secret = Yii::$app->session[nineinchnick\usr\Module::OTP_SECRET_PREFIX.'newSecret'] = $this->module->googleAuthenticator->generateSecret();

			$model->setSecret($secret);
			if ($this->module->oneTimePasswordMode === nineinchnick\usr\Module::OTP_COUNTER) {
				$this->sendEmail($model, 'oneTimePassword');
			}
		}
		$model->setSecret($secret);

		if ($model->load($_POST)) {
			if ($model->validate()) {
				// save secret
				$identity->setOneTimePasswordSecret($secret);
				Yii::$app->session[nineinchnick\usr\Module::OTP_SECRET_PREFIX.'newSecret'] = null;
				// save current code as used
				$identity->setOneTimePassword($model->oneTimePassword, $this->module->oneTimePasswordMode === nineinchnick\usr\Module::OTP_TIME ? floor(time() / 30) : $model->getPreviousCounter() + 1);
				$this->redirect('profile');
			}
		}
		if (YII_DEBUG) {
			$model->oneTimePassword = $this->module->googleAuthenticator->getCode($secret, $this->module->oneTimePasswordMode === nineinchnick\usr\Module::OTP_TIME ? null : $model->getPreviousCounter());
		}

		if ($this->module->oneTimePasswordMode === nineinchnick\usr\Module::OTP_TIME) {
			$hostInfo = Yii::$app->request->hostInfo;
			$url = $model->getUrl($identity->username, parse_url($hostInfo, PHP_URL_HOST), $secret);
		} else {
			$url = '';
		}

		return $this->render('generateOTPSecret', ['model'=>$model, 'url'=>$url]);
	}
}
