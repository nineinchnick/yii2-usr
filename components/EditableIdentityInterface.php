<?php

namespace nineinchnick\usr\components;

interface EditableIdentityInterface
{
    /**
     * Saves a new or existing identity. Does not set or change the password.
     * @see PasswordHistoryIdentityInterface::resetPassword()
     * Should detect if the email changed and mark it as not verified.
     * @param  boolean $requireVerifiedEmail
     * @return boolean
     */
    public function saveIdentity($requireVerifiedEmail = false);
    /**
     * Returns attributes like username, email, first and last name.
     * @return array
     */
    public function getIdentityAttributes();
    /**
     * Sets attributes like username, email, first and last name.
     * Password should be changed using only the resetPassword() method from the PasswordHistoryIdentityInterface.
     * @param  array   $attributes
     * @return boolean
     */
    public function setIdentityAttributes(array $attributes);
    /**
     * Maps the \nineinchnick\usr\models\ProfileForm attributes to the identity attributes
     * @see \nineinchnick\usr\models\ProfileForm::attributes()
     * @return array
     */
    public function identityAttributesMap();
}
