<?php

namespace nineinchnick\usr\commands;

use Yii;

/**
 * User managment.
 */
class UsrController extends \yii\console\Controller
{
    /**
     * @inheritdoc
     */
    public function getUniqueID()
    {
        return $this->id;
    }

    public function actionIndex()
    {
        $this->run('/help', ['usr']);
    }

    public function actionPassword($count = 1, $length = null, $extra_digit = null, $extra_char = null)
    {
        $usrModule = Yii::$app->getModule('usr');
        if ($length === null) {
            $length = $usrModule->dicewareLength;
        }
        if ($extra_digit === null) {
            $extra_digit = $usrModule->dicewareExtraDigit;
        }
        if ($extra_char === null) {
            $extra_char = $usrModule->dicewareExtraChar;
        }

        $diceware = new \nineinchnick\diceware\Diceware(Yii::$app->language);
        for ($i = 0; $i < $count; $i++) {
            echo $diceware->get_phrase($length, $extra_digit, $extra_char)."\n";
        }
    }

    /**
     * usr.manage
     *   |-usr.create
     *   |-usr.read
     *   |-usr.update
     *   |   |-usr.update.status
     *   |   |-usr.update.auth
     *   |   |-usr.update.attributes
     *   |   \-usr.update.password
     *   \-usr.delete
     */
    public function getTemplateAuthItems()
    {
        return [
            ['name' => 'usr.manage',           'child' => null],
            ['name' => 'usr.create',           'child' => 'usr.manage'],
            ['name' => 'usr.read',             'child' => 'usr.manage'],
            ['name' => 'usr.update',           'child' => 'usr.manage'],
            ['name' => 'usr.update.status',    'child' => 'usr.update'],
            ['name' => 'usr.update.auth',      'child' => 'usr.update'],
            ['name' => 'usr.update.attributes','child' => 'usr.update'],
            ['name' => 'usr.update.password',  'child' => 'usr.update'],
            ['name' => 'usr.delete',           'child' => 'usr.manage'],
        ];
    }

    public function getTemplateAuthItemDescriptions()
    {
        return [
            'usr.manage'            => Yii::t('auth', 'Manage users'),
            'usr.create'            => Yii::t('auth', 'Create users'),
            'usr.read'              => Yii::t('auth', 'Read any user'),
            'usr.update'            => Yii::t('auth', 'Update any user'),
            'usr.update.status'     => Yii::t('auth', 'Update any user\'s status'),
            'usr.update.auth'       => Yii::t('auth', 'Update any user\'s auth item assignments'),
            'usr.update.attributes' => Yii::t('auth', 'Update any user\'s attributes'),
            'usr.update.password'   => Yii::t('auth', 'Update any user\'s password'),
            'usr.delete'            => Yii::t('auth', 'Delete any user'),
        ];
    }

    public function actionCreateAuthItems()
    {
        $auth = Yii::$app->authManager;

        $newAuthItems = [];
        $descriptions = $this->getTemplateAuthItemDescriptions();
        foreach ($this->getTemplateAuthItems() as $template) {
            $newAuthItems[$template['name']] = $template;
        }
        $existingAuthItems = $auth->getPermissions();
        foreach ($existingAuthItems as $name => $existingAuthItem) {
            if (isset($newAuthItems[$name])) {
                unset($newAuthItems[$name]);
            }
        }
        foreach ($newAuthItems as $template) {
            $permission = $auth->createPermission($template['name']);
            $permission->description = $descriptions[$template['name']];
            $auth->add($permission);
            if (isset($template['child']) && $template['child'] !== null) {
                $auth->addChild($auth->getPermission($template['child']), $permission);
            }
        }
    }

    public function actionRemoveAuthItems()
    {
        $auth = Yii::$app->authManager;

        foreach ($this->getTemplateAuthItems() as $template) {
            $auth->remove($template['name']);
        }
    }

    /**
     * Creating users using this command DOES NOT send the activation email.
     * @param string  $profile          a POST (username=XX&password=YY) or JSON object with the profile form, can contain the password field
     * @param string  $authItems        a comma separated list of auth items to assign
     * @param boolean $generatePassword if true, a random password will be generated even if profile contains one
     */
    public function actionRegister($profile, $authItems = null, $generatePassword = false, $unlock = false)
    {
        $module = Yii::$app->getModule('usr');
        /** @var ProfileForm */
        $model = $module->createFormModel('ProfileForm', 'register');
        /** @var PasswordForm */
        $passwordForm = $module->createFormModel('PasswordForm', 'register');

        if (($profileData = json_decode($profile)) === null) {
            parse_str($profile, $profileData);
        }
        $model->setAttributes($profileData);
        if (isset($profile['password'])) {
            $passwordForm->setAttributes(['newPassword' => $profile['password'], 'newVerify' => $profile['password']]);
        }
        if ($generatePassword) {
            $diceware = new \nineinchnick\diceware\Diceware(Yii::$app->language);
            $password = $diceware->get_phrase($module->dicewareLength, $module->dicewareExtraDigit, $module->dicewareExtraChar);
            $passwordForm->setAttributes(['newPassword' => $password, 'newVerify' => $password]);
        }

        if ($model->validate() && $passwordForm->validate()) {
            $trx = Yii::$app->db->beginTransaction();
            if (!$model->save() || !$passwordForm->resetPassword($model->getIdentity())) {
                $trx->rollback();
                echo Yii::t('usr', 'Failed to register a new user.')."\n";

                return false;
            } else {
                $trx->commit();
                echo $model->username.' '.$passwordForm->newPassword."\n";
                $identity = $model->getIdentity();
                if ($authItems !== null) {
                    $authItems = array_map('trim', explode(',', trim($authItems, " \t\n\r\b\x0B,")));
                    $authManager = Yii::$app->authManager;
                    foreach ($authItems as $authItemName) {
                        $authManager->assign($authItemName, $identity->getId());
                    }
                }
                if ($unlock) {
                    if (!$identity->isActive()) {
                        $identity->toggleStatus($identity::STATUS_IS_ACTIVE);
                    }
                    if ($identity->isDisabled()) {
                        $identity->toggleStatus($identity::STATUS_IS_DISABLED);
                    }
                }

                return true;
            }
        }
        echo "Invalid data: ".print_r($model->getErrors(), true)."\n";

        return false;
    }
}
