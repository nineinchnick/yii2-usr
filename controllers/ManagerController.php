<?php

namespace nineinchnick\usr\controllers;

use nineinchnick\usr\components\ManagedIdentityInterface;
use nineinchnick\usr\components\PictureIdentityInterface;
use nineinchnick\usr\models\PasswordForm;
use nineinchnick\usr\models\ProfileForm;
use nineinchnick\usr\models\SearchForm;
use Yii;
use yii\filters\ContentNegotiator;
use yii\rest\Serializer;
use yii\web\Response;

/**
 * The controller handling user accounts managment.
 * @author Jan Was <jwas@nets.com.pl>
 */
class ManagerController extends UsrController
{
    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '/column2';
    /**
     * @var array context menu items. This property will be assigned to {@link CMenu::items}.
     */
    public $menu = [];

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'only' => ['index', 'update', 'delete', 'verify', 'activate', 'disable'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['usr.read'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['update'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['usr.delete'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['verify', 'activate', 'disable'],
                        'roles' => ['usr.update.status'],
                    ],
                ],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'text/html' => Response::FORMAT_HTML,
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);
        if (in_array($action->id, ['delete', 'verify', 'activate', 'disable'])) {
            if (!isset($_GET['ajax'])) {
                return $this->redirect(isset($_REQUEST['returnUrl']) ? $_REQUEST['returnUrl'] : ['index']);
            }
        }

        return $result;
    }

    protected function updateAuthItems($id, $profileForm)
    {
        $identity = $profileForm->getIdentity();
        $authManager = Yii::$app->getAuthManager();
        $assignedRoles = $id === null ? [] : $authManager->getRolesByUser($id);

        if (isset($_POST['roles']) && is_array($_POST['roles'])) {
            foreach ($_POST['roles'] as $roleName) {
                if (!isset($assignedRoles[$roleName])) {
                    $authManager->assign($roleName, $identity->getId());
                } else {
                    unset($assignedRoles[$roleName]);
                }
            }
        }
        foreach ($assignedRoles as $roleName => $role) {
            $authManager->revoke($roleName, $identity->getId());
        }
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param integer $id the ID of the model to be updated
     * @return string|\yii\web\Response
     * @throws \yii\db\Exception
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionUpdate($id = null)
    {
        if (!Yii::$app->user->can($id === null ? 'usr.create' : 'usr.update')) {
            throw new \yii\web\ForbiddenHttpException(Yii::t('yii', 'You are not authorized to perform this action.'));
        }

        /** @var ProfileForm $profileForm */
        $profileForm = $this->module->createFormModel('ProfileForm', 'manage');
        $profileForm->detachBehavior('captcha');
        if ($id !== null) {
            $profileForm->setIdentity($identity = $this->loadModel($id));
            $profileForm->setAttributes($identity->getIdentityAttributes());
        }
        $loadedProfile = $profileForm->load($_POST);
        /** @var PasswordForm $passwordForm */
        $passwordForm = $this->module->createFormModel('PasswordForm', 'register');
        $loadedPassword = isset($_POST[$passwordForm->formName()])
            && trim($_POST[$passwordForm->formName()]['newPassword']) !== ''
            && $passwordForm->load($_POST);

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $models = [];
            if ($loadedProfile) {
                $models[] = $profileForm;
            }
            if ($loadedPassword) {
                //$models[] = $passwordForm;
            }

            return \yii\widgets\ActiveForm::validateMultiple($models);
        }
        /**
         * @todo Check for detailed auth items
         */
        $canUpdateAttributes = Yii::$app->user->can('usr.update.attributes');
        $canUpdatePassword = Yii::$app->user->can('usr.update.password');
        $canUpdateAuth = Yii::$app->user->can('usr.update.auth');

        $flashes = ['success' => [], 'error' => []];
        if ($loadedProfile) {
            if ($profileForm->getIdentity() instanceof PictureIdentityInterface && !empty($profileForm->pictureUploadRules)) {
                $profileForm->picture = \yii\web\UploadedFile::getInstance($profileForm, 'picture');
            }
            $updatePassword = $canUpdatePassword && $loadedPassword;
            if ($profileForm->validate() && (!$updatePassword || $passwordForm->validate())) {
                $trx = Yii::$app->db->beginTransaction();
                $oldEmail = $profileForm->getIdentity()->getEmail();
                if (($canUpdateAttributes && !$profileForm->save($this->module->requireVerifiedEmail))
                    || ($updatePassword && !$passwordForm->resetPassword($profileForm->getIdentity()))
                ) {
                    $trx->rollback();
                    Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to register a new user.') . ' '
                        . Yii::t('usr', 'Try again or contact the site administrator.'));
                } else {
                    if ($canUpdateAuth) {
                        $this->updateAuthItems($id, $profileForm);
                    }
                    $trx->commit();
                    if ($this->module->requireVerifiedEmail && $oldEmail != $profileForm->getIdentity()->email) {
                        if ($this->sendEmail($profileForm, 'verify')) {
                            Yii::$app->session->setFlash('success', Yii::t('usr', 'An email containing further instructions has been sent to the provided email address.'));
                        } else {
                            Yii::$app->session->setFlash('error', Yii::t('usr', 'Failed to send an email.') . ' '
                                . Yii::t('usr', 'Try again or contact the site administrator.'));
                        }
                    }
                    if (!Yii::$app->session->hasFlash('success')) {
                        Yii::$app->session->setFlash('success', Yii::t('manager', 'User account has been successfully created or updated.'));
                    }

                    return $this->redirect(['index']);
                }
            }
        }

        return $this->render('update', [
            'id' => $id,
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
        if (!$this->loadModel($id)->delete()) {
            throw new \yii\web\ConflictHttpException('User account could not be deleted.');
        }
    }

    /**
     * Toggles email verification status for a particular user.
     * @param integer $id the ID of the user which email verification status is to be toggled
     */
    public function actionVerify($id)
    {
        $this->loadModel($id)->toggleStatus(ManagedIdentityInterface::STATUS_EMAIL_VERIFIED);
    }

    /**
     * Toggles active status for a particular user.
     * @param integer $id the ID of the user which active status is to be toggled
     */
    public function actionActivate($id)
    {
        $this->loadModel($id)->toggleStatus(ManagedIdentityInterface::STATUS_IS_ACTIVE);
    }

    /**
     * Toggles disabled status for a particular user.
     * @param integer $id the ID of the user which disabled status is to be toggled
     */
    public function actionDisable($id)
    {
        $this->loadModel($id)->toggleStatus(ManagedIdentityInterface::STATUS_IS_DISABLED);
    }

    /**
     * Manages all models.
     */
    public function actionIndex()
    {
        /** @var SearchForm $model */
        $model = $this->module->createFormModel('SearchForm');
        if (isset($_REQUEST['SearchForm'])) {
            $model->attributes = $_REQUEST['SearchForm'];
            $model->validate();
            $errors = $model->getErrors();
            $model->setAttributes(array_fill_keys(array_keys($errors), null));
        }

        if (Yii::$app->response->format === Response::FORMAT_JSON) {
            $serializer = new Serializer;
            return $serializer->serialize($model->getIdentity()->getDataProvider($model));
        }

        return $this->render('index', ['model' => $model]);
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param  integer        $id the ID of the model to be loaded
     * @return User           the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {
        /** @var SearchForm $model */
        $searchForm = $this->module->createFormModel('SearchForm');
        if (($model = $searchForm->getIdentity($id)) === null) {
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }

        return $model;
    }
}
