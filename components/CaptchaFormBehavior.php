<?php
/**
 * CaptchaFormBehavior class file.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */

namespace nineinchnick\usr\components;

use Yii;

/**
 * CaptchaFormBehavior adds captcha validation to a form model component.
 * The model should extend from {@link Model} or its child classes.
 *
 * @property Model $owner The owner model that this behavior is attached to.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class CaptchaFormBehavior extends FormModelBehavior
{
    public $verifyCode;

    /**
     * @inheritdoc
     */
    public function filterRules($rules = [])
    {
        $module = Yii::$app->controller !== null ? Yii::$app->controller->module : null;
        $behaviorRules = [
            ['verifyCode', 'captcha', 'captchaAction' => ($module !== null ? $module->id : 'usr').'/default/captcha'],
        ];

        return array_merge($rules, $this->applyRuleOptions($behaviorRules));
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'verifyCode' => Yii::t('usr', 'Verification code'),
        ];
    }
}
