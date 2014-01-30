<?php

namespace nineinchnick\usr\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\AccessDeniedHttpException;

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
		$returnUrlParts = explode('/',Yii::$app->user->returnUrl);
		if(end($returnUrlParts)=='index.php'){
			$url = '/';
		}else{
			$url = Yii::$app->user->returnUrl;
		}
		return $this->redirect($url);
	}

	public function actionLogin($provider=null)
	{
		if ($provider!==null)
			$_POST['HybridauthForm']['provider'] = $provider;
		/** @var HybridauthForm */
		$remoteLogin = $this->module->createFormModel('HybridauthForm');
		/** @var LoginForm */
		$localLogin = $this->module->createFormModel('LoginForm', 'hybridauth');
		/** @var ProfileForm */
		$localProfile = $this->module->createFormModel('ProfileForm', 'register');

		if(isset($_POST['ajax'])) {
			if ($_POST['ajax']==='remoteLogin-form')
				echo CActiveForm::validate($remoteLogin);
			elseif ($_POST['ajax']==='localProfile-form')
				echo CActiveForm::validate($localProfile);
			else
				echo CActiveForm::validate($localLogin);
			Yii::$app->end();
		}

		if (isset($_POST['HybridauthForm'])) {
			$remoteLogin->setAttributes($_POST['HybridauthForm']);
			$remoteLogin->scenario = strtolower($remoteLogin->provider);
			$remoteLogin->setValidProviders($this->module->hybridauthProviders);
			$remoteLogin->setHybridAuth($this->module->getHybridAuth());

			if($remoteLogin->validate()) {
				if ($remoteLogin->login()) {
					// user is already associated with remote identity and has been logged in
					$this->afterLogin();
				} elseif (($adapter=$remoteLogin->getHybridAuthAdapter()) !== null && $adapter->isUserConnected()) {
					if (!Yii::$app->user->isGuest) {
						// user is already logged in and needs to be associated with remote identity
						if (!$remoteLogin->associate(Yii::$app->user->getId())) {
							Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to associate current user with {provider}.', ['provider'=>$remoteLogin->provider]));
							return $this->redirect('login');
						}
					} else {
						// first try to log in if form has been filled
						$localLogin = $this->performLocalLogin($localLogin, $remoteLogin);
						// then try to register a new local profile
						if ($this->module->registrationEnabled) {
							$localProfile = $this->registerLocalProfile($localProfile, $remoteLogin);
						}
					}
				} else {
					Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to log in using {provider}.', ['provider'=>$remoteLogin->provider]));
					return $this->redirect('login');
				}
				return $this->render('associate', [
					'remoteLogin'=>$remoteLogin,
					'localLogin'=>$localLogin,
					'localProfile'=>$localProfile,
				]);
				return;
			}
		}
		return $this->render('login', [
			'remoteLogin'=>$remoteLogin,
			'localLogin'=>$localLogin,
			'localProfile'=>$localProfile,
		]);
	}

	protected function performLocalLogin(\nineinchnick\usr\models\LoginForm $localLogin, \nineinchnick\usr\models\HybridauthForm $remoteLogin)
	{
		if(!isset($_POST['LoginForm'])) {
			return $localLogin;
		}
		$localLogin->setAttributes($_POST['LoginForm']);
		if($localLogin->validate() && $localLogin->login()) {
			// don't forget to associate the new profile with remote provider
			if (!$remoteLogin->associate($localLogin->getIdentity()->getId())) {
				Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to associate current user with {provider}.', ['provider'=>$remoteLogin->provider]));
				return $this->redirect('login');
			}

			$this->afterLogin();
		}
		return $localLogin;
	}

	protected function registerLocalProfile(\nineinchnick\usr\models\ProfileForm $localProfile, \nineinchnick\usr\models\HybridauthForm $remoteLogin)
	{
		if (isset($_POST['ProfileForm'])) {
			$localProfile->setAttributes($_POST['ProfileForm']);

			if ($localProfile->validate()) {
				$trx = Yii::$app->db->beginTransaction();
				if (!$localProfile->save()) {
					$trx->rollback();
					Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to register a new user.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
				} else {
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
						Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to associate current user with {provider}.', ['provider'=>$remoteLogin->provider]));
						return $this->redirect('login');
					}

					if ($localProfile->getIdentity()->isActive()) {
						// don't use the $localProfile->login() method because there is no password set so we can't authenticate this identity
						if (Yii::$app->user->login($localProfile->getIdentity(),0)) {
							$this->afterLogin();
						} else {
							Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to log in.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
						}
					} else {
						if (!Yii::$app->session->hasFlash('success'))
							Yii::$app->session->setFlash('success', Yii::t('usr', 'Please wait for the account to be activated. A notification will be send to provided email address.'));
						return $this->redirect(['login']);
					}
				}
			}
		} else {
			$profile = $remoteLogin->getHybridAuthAdapter()->getUserProfile();
			$email = (isset($profile->emailVerifier) && $profile->emailVerifier !== null) ? $profile->emailVerifier : $profile->email;
			$localProfile->setAttributes([
				'username' => $email,
				'email' => $email,
				'firstName' => $profile->firstName,
				'lastName' => $profile->lastName,
			]);
		}
		return $localProfile;
	}

	public function actionCallback()
	{
		\Hybrid_Endpoint::process();
	}
}
