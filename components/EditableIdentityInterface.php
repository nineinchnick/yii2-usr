<?php

namespace nineinchnick\usr\components;

interface EditableIdentityInterface
{
	/**
	 * Saves a new or existing identity. Does not set or change the password. @see IPasswordHistoryIdentity.resetPassword()
	 * Should detect if the email changed and mark it as not verified.
	 * @return boolean
	 */
	public function saveIdentity();
	/**
	 * Returns attributes like username, email, first and last name.
	 * @return array
	 */
	public function getIdentityAttributes();
	/**
	 * Sets attributes like username, email, first and last name.
	 * Password should be changed using only the resetPassword() method from the IPasswordHistoryIdentity interface.
	 * @return boolean
	 */
	public function setIdentityAttributes(array $attributes);
}

