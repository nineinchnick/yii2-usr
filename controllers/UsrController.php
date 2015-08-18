<?php

namespace nineinchnick\usr\controllers;

use Yii;

abstract class UsrController extends \yii\web\Controller
{
    /**
     * Sends out an email containing instructions and link to the email verification
     * or password recovery page, containing an activation key.
     * @param  \yii\base\Model $model it must have a getIdentity() method
     * @param  string     $mode  'recovery', 'verify' or 'oneTimePassword'
     * @return boolean    if sending the email succeeded
     */
    public function sendEmail(\yii\base\Model $model, $mode)
    {
        $params = [
            'siteUrl' => \yii\helpers\Url::toRoute(['/'], true),
        ];
        switch ($mode) {
            default:
                return false;
            case 'recovery':
            case 'verify':
                $subject = $mode == 'recovery'
                    ? Yii::t('usr', 'Password recovery')
                    : Yii::t('usr', 'Email address verification');
                $params['actionUrl'] = \yii\helpers\Url::toRoute([
                    $this->module->id . '/default/' . $mode,
                    'activationKey' => $model->getIdentity()->getActivationKey(),
                    'username'      => $model->getIdentity()->username,
                ], true);
                break;
            case 'oneTimePassword':
                $subject        = Yii::t('usr', 'One Time Password');
                $params['code'] = $model->getNewCode();
                break;
        }
        $message = Yii::$app->mailer->compose($mode, $params);
        $message->setTo([$model->getIdentity()->getEmail() => $model->getIdentity()->username]);
        $message->setSubject($subject);

        return $message->send();
    }

    /**
     * Retreive view name and params based on scenario name and module configuration.
     *
     * @param  string $scenario
     * @param  string $default  default view name if scenario is null
     * @return array  two values, view name (string) and view params (array)
     */
    public function getScenarioView($scenario, $default)
    {
        if (empty($scenario) || $scenario === \yii\base\Model::SCENARIO_DEFAULT) {
            $scenario = $default;
        }
        if (!isset($this->module->scenarioViews[$scenario])) {
            return [$scenario, []];
        }
        // config, scenario, default
        $config = $this->module->scenarioViews[$scenario];
        if (isset($config['view'])) {
            $view = $config['view'];
            unset($config['view']);
        } else {
            $view = $scenario;
        }

        return [$view, $config];
    }

    /**
     * Redirects user either to returnUrl or main page.
     */
    public function afterLogin()
    {
        $returnUrl = Yii::$app->user->returnUrl;
        $returnUrlParts = explode('/', is_array($returnUrl) ? reset($returnUrl) : $returnUrl);
        $url = end($returnUrlParts) == 'index.php' ? '/' : Yii::$app->user->returnUrl;

        return $this->redirect($url);
    }
}
