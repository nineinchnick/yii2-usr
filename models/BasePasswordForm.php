<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * BasePasswordForm class.
 * BasePasswordForm is the base class for forms used to set new password.
 */
abstract class BasePasswordForm extends BaseUsrForm
{
	public $newPassword;
	public $newVerify;

	private $_passwordStrengthRules;

	public function getPasswordStrengthRules()
	{
		if ($this->_passwordStrengthRules === null) {
			$this->_passwordStrengthRules = [
				['newPassword', 'string', 'min' => 8, 'message' => Yii::t('usr', 'New password must contain at least 8 characters.')],
				['newPassword', 'match', 'pattern' => '/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', 'message'	=> Yii::t('usr', 'New password must contain at least one lower and upper case character and a digit.')],
			];
		}
		return $this->_passwordStrengthRules;
	}

	public function setPasswordStrengthRules($value)
	{
		$this->_passwordStrengthRules = $value;
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		$rules = array_merge(
			[
				[['newPassword', 'newVerify'], 'filter', 'filter'=>'trim'],
				[['newPassword', 'newVerify'], 'required'],
				['newPassword', 'unusedNewPassword'],
			],
			$this->passwordStrengthRules,
			[
				['newVerify', 'compare', 'compareAttribute'=>'newPassword', 'message' => Yii::t('usr', 'Please type the same new password twice to verify it.')],
			]
		);
		return $rules;
	}

	public function rulesAddScenario($rules, $scenario)
	{
		foreach($rules as $key=>$rule) {
			$rules[$key]['on'] = $scenario;
		}
		return $rules;
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), [
			'newPassword'	=> Yii::t('usr','New password'),
			'newVerify'		=> Yii::t('usr','Verify'),
		]);
	}

	abstract public function getIdentity();
	abstract public function resetPassword();

	/**
	 * Checkes if current password hasn't been used before.
	 * This is the 'unusedNewPassword' validator as declared in rules().
	 */
	public function unusedNewPassword()
	{
		if($this->hasErrors()) {
			return;
		}

		$identity = $this->getIdentity();
		// check if new password hasn't been used before
		if ($identity instanceof PasswordHistoryIdentityInterface) {
			if (($lastUsed = $identity->getPasswordDate($this->newPassword)) !== null) {
				$this->addError('newPassword',Yii::t('usr','New password has been used before, last set on {date}.', ['date'=>$lastUsed]));
				return false;
			}
			return true;
		}
		// check if new password is not the same as current one
		if ($identity !== null) {
			$newIdentity = clone $identity;
			$newIdentity->password = $this->newPassword;
			if ($newIdentity->authenticate()) {
				$this->addError('newPassword',Yii::t('usr','New password must be different than the old one.'));
				return false;
			}
		}
		return true;
	}
}
