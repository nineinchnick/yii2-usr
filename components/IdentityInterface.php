<?php

namespace nineinchnick\usr\components;

interface IdentityInterface extends \yii\web\IdentityInterface
{
	/**
	 * Authenticates a user.
	 * @param string $password
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate($password);
}
