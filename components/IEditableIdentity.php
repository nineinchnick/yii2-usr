<?php

interface IEditableIdentity
{
	/**
	 * Saves a new or existing identity. Does not set or change the password. @see IPasswordHistoryIdentity.resetPassword()
	 * Should detect if the email changed and mark it as not verified.
	 * @return boolean
	 */
	public function save();
	/**
	 * Returns attributes like username, email, first and last name.
	 * @return array
	 */
	public function getAttributes();
	/**
	 * Sets attributes like username, email, first and last name.
	 * Password should be changed using only the resetPassword() method from the IPasswordHistoryIdentity interface.
	 * @return boolean
	 */
	public function setAttributes(array $attributes);
}

