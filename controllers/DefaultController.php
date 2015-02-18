<?php

namespace nineinchnick\usr\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\AccessDeniedHttpException;
use yii\web\NotFoundHttpException;
use nineinchnick\usr\components\PictureIdentityInterface;
use nineinchnick\usr\components\ActivatedIdentityInterface;

/**
 * The default controller providing all basic actions.
 * @author Jan Was <jwas@nets.com.pl>
 */
class DefaultController extends UsrController
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = [];
        if ($this->module->captcha !== null) {
            // captcha action renders the CAPTCHA image
            // displayed on the register and recovery page
            $actions['captcha'] = [
                'class' => '\yii\captcha\CaptchaAction',
                'backColor' => 0xFFFFFF,
                'testLimit' => 0,
            ];
        }
        if ($this->module->dicewareEnabled) {
            // DicewareAction generates a random passphrase
            $actions['password'] = [
                'class' => '\nineinchnick\usr\components\DicewareAction',
                'length' => $this->module->dicewareLength,
                'extraDigit' => $this->module->dicewareExtraDigit,
                'extraChar' => $this->module->dicewareExtraChar,
            ];
        }
        if (isset($this->module->loginFormBehaviors['oneTimePasswordBehavior'])) {
            $configuration = $this->module->loginFormBehaviors['oneTimePasswordBehavior'];
            if ($configuration['mode'] != \nineinchnick\usr\components\OneTimePasswordFormBehavior::OTP_NONE) {
                if (!isset($configuration['authenticator'])) {
                    $configuration['authenticator'] = \nineinchnick\usr\components\OneTimePasswordFormBehavior::getDefaultAuthenticator();
                }
                // OneTimePasswordAction allows toggling two step auth in user profile
                $actions['toggleOneTimePassword'] = [
                    'class' => '\nineinchnick\usr\components\OneTimePasswordAction',
                    'configuration' => $configuration,
                ];
            }
        }

        return $actions;
    }

    /**
     * Redirect user depending on whether is he logged in or not.
     * Performs additional authorization checks.
     * @param  Action  $action the action to be executed.
     * @return boolean whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        switch ($action->id) {
        case 'index':
        case 'profile':
        case 'profilePicture':
            if (Yii::$app->user->isGuest) {
                $this->redirect(['login']);

                return false;
            }
            break;
        case 'login':
        case 'recovery':
            if ($action->id === 'recovery' && !$this->module->recoveryEnabled) {
                throw new AccessDeniedHttpException(Yii::t('usr', 'Password recovery has not been enabled.'));
            }
            if (!Yii::$app->user->isGuest) {
                $this->goBack();

                return false;
            }
            break;
        case 'register':
            if (!$this->module->registrationEnabled) {
                throw new AccessDeniedHttpException(Yii::t('usr', 'Registration has not been enabled.'));
            }
            if (!Yii::$app->user->isGuest) {
                $this->redirect(['profile']);

                return false;
            }
            break;
        case 'verify':
            if (!isset($_GET['activationKey'])) {
                throw new BadRequestHttpException(Yii::t('usr', 'Activation key is missing.'));
            }
            break;
        }

        return true;
    }

    /**
     * Users are redirected to their profile if logged in and to login page otherwise.
     */
    public function actionIndex()
    {
        return $this->redirect(['profile']);
    }

    /**
     * Performs user login, expired password reset or one time password verification.
     * @param  string $scenario
     * @return string
     */
    public function actionLogin($scenario = null)
    {
        /** @var LoginForm */
        $model = $this->module->createFormModel('LoginForm');
        $scenarios = $model->scenarios();
        if ($scenario !== null && in_array($scenario, array_keys($scenarios))) {
            $model->scenario = $scenario;
        }

        if ($model->load($_POST)) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

                return \yii\widgets\ActiveForm::validate($model);
            }
            if ($model->validate()) {
                if (($model->scenario !== 'reset' || $model->resetPassword($model->newPassword)) && $model->login($this->module->rememberMeDuration)) {
                    return $this->afterLogin();
                } else {
                    Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to change password or log in using new password.'));
                }
            }
        }
        list($view, $params) = $this->getScenarioView($model->scenario, 'login');

        return $this->render($view, array_merge(['model' => $model], $params));
    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout()
    {
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        return $this->goHome();
    }

    /**
     * Processes a request for password recovery email or resetting the password.
     * @return string
     */
    public function actionRecovery()
    {
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
            if ($model->activationKey !== null) {
                $model->scenario = 'reset';
            }
            if ($model->validate()) {
                if ($model->scenario !== 'reset') {
                    /**
                     * Send email appropriate to the activation status. If verification is required, that must happen
                     * before password recovery. Also allows re-sending of verification emails.
                     */
                    if ($this->sendEmail($model, $model->identity->isActive() ? 'recovery' : 'verify')) {
                        Yii::$app->session->setFlash('success', Yii::t('usr', 'An email containing further instructions has been sent to email associated with specified user account.'));
                    } else {
                        Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to send an email.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
                    }
                } else {
                    // a valid recovery form means the user confirmed his email address
                    $model->getIdentity()->verifyEmail($this->module->requireVerifiedEmail);
                    // regenerate the activation key to prevent reply attack
                    $model->getIdentity()->getActivationKey();
                    if ($model->resetPassword() && $model->login()) {
                        return $this->afterLogin();
                    } else {
                        Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to change password or log in using new password.'));
                    }
                }

                return $this->redirect(['recovery']);
            }
        }

        return $this->render('recovery', ['model' => $model]);
    }

    /**
     * Processes email verification.
     * @return string
     */
    public function actionVerify()
    {
        /** @var RecoveryForm */
        $model = $this->module->createFormModel('RecoveryForm', 'verify');
        $model->setAttributes($_GET);
        if ($model->validate() && $model->getIdentity()->verifyEmail($this->module->requireVerifiedEmail)) {
            // regenerate the activation key to prevent reply attack
            $model->getIdentity()->getActivationKey();
            Yii::$app->session->setFlash('success', Yii::t('usr', 'Your email address has been successfully verified.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to verify your email address.'));
        }

        return $this->redirect([Yii::$app->user->isGuest ? 'login' : 'profile']);
    }

    /**
     * Performs user sign-up.
     * @return string
     */
    public function actionRegister()
    {
        /** @var ProfileForm */
        $model = $this->module->createFormModel('ProfileForm', 'register');
        /** @var PasswordForm */
        $passwordForm = $this->module->createFormModel('PasswordForm', 'register');

        if ($model->load($_POST)) {
            $passwordForm->load($_POST);
            if ($model->getIdentity() instanceof PictureIdentityInterface && !empty($model->pictureUploadRules)) {
                $model->picture = \yii\web\UploadedFile::getInstance($model, 'picture');
            }
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

                return \yii\widgets\ActiveForm::validate($model, $passwordForm);
            }
            if ($model->validate() && $passwordForm->validate()) {
                $trx = Yii::$app->db->beginTransaction();
                if (!$model->save($this->module->requireVerifiedEmail) || !$passwordForm->resetPassword($model->getIdentity())) {
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
                    if (!($model->getIdentity() instanceof ActivatedIdentityInterface) || $model->getIdentity()->isActive()) {
                        if ($model->login()) {
                            return $this->afterLogin();
                        } else {
                            Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to log in.').' '.Yii::t('usr', 'Try again or contact the site administrator.'));
                        }
                    } else {
                        if (!Yii::$app->session->hasFlash('success')) {
                            Yii::$app->session->setFlash('success', Yii::t('usr', 'Please wait for the account to be activated. A notification will be send to provided email address.'));
                        }

                        return $this->redirect(['login']);
                    }
                }
            }
        }

        return $this->render('updateProfile', ['model' => $model, 'passwordForm' => $passwordForm]);
    }

    /**
     * Allows users to view or update their profile.
     * @param  boolean $update
     * @return string
     */
    public function actionProfile($update = false)
    {
        /** @var ProfileForm */
        $model = $this->module->createFormModel('ProfileForm');
        $model->setAttributes($model->getIdentity()->getIdentityAttributes());
        $loadedModel = $model->load($_POST);
        /** @var PasswordForm */
        $passwordForm = $this->module->createFormModel('PasswordForm');
        $loadedPassword = isset($_POST[$passwordForm->formName()]) && trim($_POST[$passwordForm->formName()]['newPassword']) !== '' && $passwordForm->load($_POST);
        if ($loadedModel) {
            if ($model->getIdentity() instanceof PictureIdentityInterface && !empty($model->pictureUploadRules)) {
                $model->picture = \yii\web\UploadedFile::getInstance($model, 'picture');
            }
            $passwordForm->password = $model->password;
        }

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
        $flashes = ['success' => [], 'error' => []];
        /**
         * Only try to set new password if it has been specified in the form.
         * The current password could have been used to authorize other changes.
         */
        if ($loadedPassword) {
            if ($passwordForm->validate() && $passwordForm->resetPassword($model->getIdentity())) {
                $flashes['success'][] = Yii::t('usr', 'Changes have been saved successfully.');
            } else {
                $flashes['error'][] = Yii::t('usr', 'Failed to change password.');
            }
        }
        if ($loadedModel && empty($flashes['error'])) {
            if ($model->validate()) {
                $oldEmail = $model->getIdentity()->getEmail();
                if ($model->save($this->module->requireVerifiedEmail)) {
                    if ($this->module->requireVerifiedEmail && $oldEmail != $model->email) {
                        if ($this->sendEmail($model, 'verify')) {
                            $flashes['success'][] = Yii::t('usr', 'An email containing further instructions has been sent to provided email address.');
                        } else {
                            $flashes['error'][] = Yii::t('usr', 'Failed to send an email.').' '.Yii::t('usr', 'Try again or contact the site administrator.');
                        }
                    }
                    $flashes['success'][] = Yii::t('usr', 'Changes have been saved successfully.');
                    if (!empty($flashes['success'])) {
                        Yii::$app->session->setFlash('success', implode('<br/>', $flashes['success']));
                    }
                    if (!empty($flashes['error'])) {
                        Yii::$app->session->setFlash('error', implode('<br/>', $flashes['error']));
                    }

                    return $this->redirect(['profile']);
                } else {
                    $flashes['error'][] = Yii::t('usr', 'Failed to update profile.').' '.Yii::t('usr', 'Try again or contact the site administrator.');
                }
            }
        }
        if (!empty($flashes['success'])) {
            Yii::$app->session->setFlash('success', implode('<br/>', $flashes['success']));
        }
        if (!empty($flashes['error'])) {
            Yii::$app->session->setFlash('error', implode('<br/>', $flashes['error']));
        }
        if ($update) {
            return $this->render('updateProfile', ['model' => $model, 'passwordForm' => $passwordForm]);
        } else {
            return $this->render('viewProfile', ['model' => $model]);
        }
    }

    /**
     * Allows users to view their profile picture.
     * @param  integer $id
     * @return string
     */
    public function actionProfilePicture($id)
    {
        /** @var ProfileForm */
        $model = $this->module->createFormModel('ProfileForm');
        if (!(($identity = $model->getIdentity()) instanceof PictureIdentityInterface)) {
            throw new ForbiddenException(Yii::t('usr', 'The {class} class must implement the {interface} interface.', [
                'class' => get_class($identity),
                'interface' => 'PictureIdentityInterface',
            ]));
        }
        $picture = $identity->getPicture($id);
        if ($picture === null) {
            throw new NotFoundHttpException(Yii::t('usr', 'Picture with id {id} is not found.', ['id' => $id]));
        }
        header('Content-Type:'.$picture['mimetype']);
        echo $picture['picture'];
    }
}
