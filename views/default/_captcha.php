				<?= $form->field($model, 'verifyCode', ['hint' => Yii::t('usr', 'Please enter the letters as they are shown in the image above.').'<br/>'.Yii::t('usr', 'Letters are not case-sensitive.')])
					->widget('yii\captcha\Captcha', $module->captcha === true ? [] : $module->captcha) ?>
