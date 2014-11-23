                <?= $form->field($model, 'verifyCode', ['enableAjaxValidation' => false])
                ->widget('yii\captcha\Captcha', array_merge(['captchaAction' => 'default/captcha'], $this->context->module->captcha === true ? [] : $this->context->module->captcha))
                ->hint(Yii::t('usr', 'Please enter the letters as they are shown in the image above.').'<br/>'.Yii::t('usr', 'Letters are not case-sensitive.')) ?>
