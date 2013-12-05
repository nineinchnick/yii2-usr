<?php

namespace nineinchnick\usr\components;

interface IdentityInterface extends \yii\web\IdentityInterface
{
	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate();
}
