<?php
/**
 * CaptchaFormBehavior class file.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */

/**
 * CaptchaFormBehavior adds captcha validation to a form model component.
 * The model should extend from {@link CFormModel} or its child classes.
 *
 * @property CFormModel $owner The owner model that this behavior is attached to.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class CaptchaFormBehavior extends FormModelBehavior
{
	public $verifyCode;

	public function rules()
	{
		$rules = array(
			array('verifyCode', 'captcha'),
		);
		return $this->applyRuleOptions($rules);
	}

	public function attributeLabels()
	{
		return array(
			'verifyCode' => Yii::t('UsrModule.usr','Verification code'),
		);
	}
}
