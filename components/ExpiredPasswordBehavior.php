<?php
/**
 * ExpiredPasswordBehavior class file.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */

/**
 * ExpiredPasswordBehavior adds captcha validation to a form model component.
 * The model should extend from {@link CFormModel} or its child classes.
 *
 * @property CFormModel $owner The owner model that this behavior is attached to.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class ExpiredPasswordBehavior extends FormModelBehavior
{
	private $_passwordTimeout;

	public function getPasswordTimeout()
	{
		return $this->_passwordTimeout;
	}

	public function setPasswordTimeout($value)
	{
		$this->_passwordTimeout = $value;
	}

	public function rules()
	{
		$rules = array(
			array('password', 'passwordHasNotExpired', 'except'=>'reset, hybridauth, verifyOTP'),
		);
		return $this->applyRuleOptions($rules);
	}

	public function passwordHasNotExpired()
	{
		if($this->owner->hasErrors()) {
			return;
		}

		$identity = $this->owner->getIdentity();
		if (!($identity instanceof IPasswordHistoryIdentity))
			throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>get_class($identity),'{interface}'=>'IPasswordHistoryIdentity')));
		$lastUsed = $identity->getPasswordDate();
		$lastUsedDate = new DateTime($lastUsed);
		$today = new DateTime();
		if ($lastUsed === null || $today->diff($lastUsedDate)->days >= $this->passwordTimeout) {
			if ($lastUsed === null) {
				$this->owner->addError('password',Yii::t('UsrModule.usr','This is the first time you login. Current password needs to be changed.'));
			} else {
				$this->owner->addError('password',Yii::t('UsrModule.usr','Current password has been used too long and needs to be changed.'));
			}
			$this->owner->scenario = 'reset';
			return false;
		}

		return true;
	}
}
