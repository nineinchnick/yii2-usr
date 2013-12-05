<?php

namespace nineinchnick\usr\controllers;

use Yii;

abstract class UsrController extends \yii\web\Controller
{
	/**
	 * Sends out an email containing instructions and link to the email verification
	 * or password recovery page, containing an activation key.
	 * @param CFormModel $model it must have a getIdentity() method
	 * @param strign $mode 'recovery', 'verify' or 'oneTimePassword'
	 * @return boolean if sending the email succeeded
	 */
	public function sendEmail(\yii\base\Model $model, $mode)
	{
		$params = ['siteUrl' => Yii::$app->getUrlManager()->createAbsoluteUrl('/')];
		switch($mode) {
		default: return false;
		case 'recovery':
		case 'verify':
			$subject = $mode == 'recovery' ? Yii::t('usr', 'Password recovery') : Yii::t('usr', 'Email address verification');
			$params['actionUrl'] = Yii::$app->getUrlManager()->createAbsoluteUrl('default/'.$mode, array(
				'activationKey'=>$model->getIdentity()->getActivationKey(),
				'username'=>$model->getIdentity()->username,
			));
			break;
		case 'oneTimePassword':
			$subject = Yii::t('usr', 'One Time Password');
			$params['code'] = $model->getNewCode();
			break;
		}
		$message = Yii::$app->mail->compose($mode, $params);
		$message->setTo([$model->getIdentity()->getEmail() => $model->getIdentity()->username]);
		$message->setSubject($subject);
		if ($message->send()) {
			return true;
		} else {
			Yii::error($message->ErrorInfo, 'usr');
			return false;
		}
	}
}
