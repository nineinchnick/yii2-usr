<?php

namespace nineinchnick\usr\components;

interface IdentityInterface extends \yii\web\IdentityInterface
{
    const ERROR_NONE = 0;
    const ERROR_INVALID = 1;
    const ERROR_INACTIVE = 2;
    const ERROR_DISABLED = 3;
    /**
     * Authenticates a user.
     * @param  string $password
     * @return mixed  boolean true whether authentication succeeds or an array of error code and error message.
     */
    public function authenticate($password);
}
