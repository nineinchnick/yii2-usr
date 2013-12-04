<?php

abstract class UsrController extends CController
{
	protected function displayFlashes()
	{
		if (($flashMessages = Yii::app()->user->getFlashes())) {
			echo '<ul class="flashes">';
			foreach($flashMessages as $key => $message) {
				echo '<li><div class="'.$this->module->alertCssClassPrefix.$key.'">'.$message.'</div></li>';
			}
			echo '</ul>';
		}
	}

	/**
	 * Sends out an email containing instructions and link to the email verification
	 * or password recovery page, containing an activation key.
	 * @param CFormModel $model it must have a getIdentity() method
	 * @param strign $mode 'recovery', 'verify' or 'oneTimePassword'
	 * @return boolean if sending the email succeeded
	 */
	public function sendEmail(CFormModel $model, $mode)
	{
		$mail = $this->module->mailer;
		$mail->AddAddress($model->getIdentity()->getEmail(), $model->getIdentity()->getName());
		$params = array(
			'siteUrl' => $this->createAbsoluteUrl('/'), 
		);
		switch($mode) {
		default: return false;
		case 'recovery':
		case 'verify':
			$mail->Subject = $mode == 'recovery' ? Yii::t('UsrModule.usr', 'Password recovery') : Yii::t('UsrModule.usr', 'Email address verification');
			$params['actionUrl'] = $this->createAbsoluteUrl('default/'.$mode, array(
				'activationKey'=>$model->getIdentity()->getActivationKey(),
				'username'=>$model->getIdentity()->getName(),
			));
			break;
		case 'oneTimePassword':
			$mail->Subject = Yii::t('UsrModule.usr', 'One Time Password');
			$params['code'] = $model->getNewCode();
			break;
		}
		$body = $this->renderPartial($mail->getPathViews().'.'.$mode, $params, true);
		$full = $this->renderPartial($mail->getPathLayouts().'.email', array('content'=>$body), true);
		$mail->MsgHTML($full);
		if ($mail->Send()) {
			return true;
		} else {
			Yii::log($mail->ErrorInfo, 'error');
			return false;
		}
	}
}
