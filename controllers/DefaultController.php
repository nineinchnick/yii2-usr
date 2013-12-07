<?php

namespace nineinchnick\usr\controllers;

use Yii;
use yii\web\HttpException;

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
		return $actions;
	}

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

	public function actionLogin($scenario = null)
	{
		if (!Yii::$app->user->isGuest)
			return $this->goBack();

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

	public function actionRecovery()
	{
		if (!$this->module->recoveryEnabled) {
			throw new HttpException(403,Yii::t('usr', 'Password recovery has not been enabled.'));
		}
		if (!Yii::$app->user->isGuest)
			$this->redirect(Yii::$app->user->returnUrl);

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

	public function actionVerify()
	{
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

	public function actionRegister()
	{
		if (!$this->module->registrationEnabled) {
			throw new HttpException(403,Yii::t('usr', 'Registration has not been enabled.'));
		}
		if (!Yii::$app->user->isGuest)
			$this->redirect(['profile']);

		$model = $this->module->createFormModel('ProfileForm', 'register');
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

	public function actionProfile($update=false)
	{
		if (Yii::$app->user->isGuest)
			$this->redirect(['login']);

		$model = $this->module->createFormModel('ProfileForm');
		$model->setAttributes($model->getIdentity()->getIdentityAttributes());
		$loadedModel = $model->load($_POST);
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

	protected function displayOneTimePasswordSecret()
	{
		$model = new OneTimePasswordForm;
		$identity = $model->getIdentity();
		$secret = $identity->getOneTimePasswordSecret();
		/*
		if ($secret === null && $this->module->oneTimePasswordRequired) {
			$googleAuthenticator = $this->module->googleAuthenticator;
			$secret = $googleAuthenticator->generateSecret();
			$identity->setOneTimePasswordSecret($secret);
		}
		$hostInfo = Yii::$app->request->hostInfo;
		$url = $model->getUrl($identity->username, parse_url($hostInfo, PHP_URL_HOST), $secret);
		 */
		if ($secret === null) {
			$label = CHtml::link(Yii::t('usr', 'Enable'), ['toggleOneTimePassword']);
		} else {
			$label = CHtml::link(Yii::t('usr', 'Disable'), ['toggleOneTimePassword']);
			/*if ($this->module->oneTimePasswordMode === nineinchnick\usr\Module::OTP_TIME) {
				$label .= '<br/>'.CHtml::image($url, Yii::t('usr', 'One Time Password Secret'));
			}*/
		}
		return $label;
	}

	public function actionToggleOneTimePassword()
	{
		if (Yii::$app->user->isGuest)
			$this->redirect(['login']);
		if ($this->module->oneTimePasswordRequired)
			$this->redirect(['profile']);

		$model = new OneTimePasswordForm;
		$identity = $model->getIdentity();
		if ($identity->getOneTimePasswordSecret() !== null) {
			$identity->setOneTimePasswordSecret(null);
			Yii::$app->request->cookies->remove(nineinchnick\usr\Module::OTP_COOKIE);
			$this->redirect('profile');
			return;
		}

		$model->setMode($this->module->oneTimePasswordMode)->setAuthenticator($this->module->googleAuthenticator);

		// generate a secret and save it in session if it hasn't been done yet
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

	public function actionPassword()
	{
		require(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'/components/'.DIRECTORY_SEPARATOR.'Diceware.php');
		$diceware = new \Diceware(Yii::$app->language);
		$password = $diceware->get_phrase($this->module->dicewareLength, $this->module->dicewareExtraDigit, $this->module->dicewareExtraChar);
		echo json_encode($password);
	}
}
