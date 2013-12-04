<?php

interface IActivatedIdentity
{
	const ERROR_AKEY_NONE=0;
	const ERROR_AKEY_INVALID=1;
	const ERROR_AKEY_TOO_OLD=2;

	/**
	 * Loads a specific user identity using one of supplied attributes, such as username or email.
	 * @param array $attributes contains at least one of keys: 'username', 'email'
	 * @return object a user identity object or null if not found.
	 */
	public static function find(array $attributes);
	/**
	 * Checkes if user account is active. This should not include disabled (banned) status.
	 * This could include if the email address has been verified.
	 * Same checks should be done in the authenticate() method, because this method is not called before logging in.
	 * @return boolean
	 */
	public function isActive();
	/**
	 * Checkes if user account is disabled (banned). This should not include active status.
	 * @return boolean
	 */
	public function isDisabled();
	/**
	 * Generates and saves a new activation key used for verifying email and restoring lost password.
	 * The activation key is then sent by email to the user.
	 *
	 * Note: only the last generated activation key should be valid and an activation key
	 * should have it's generation date saved to verify it's age later.
	 *
	 * @return string
	 */
	public function getActivationKey();
	/**
	 * Verifies if specified activation key matches the saved one and if it's not too old.
	 * This method should not alter any saved data.
	 * @return integer the verification error code. If there is an error, the error code will be non-zero.
	 */
	public function verifyActivationKey($activationKey);
	/**
	 * Verify users email address, which could also activate his account and allow him to log in.
	 * Call only after verifying the activation key.
	 * @return boolean
	 */
	public function verifyEmail();
	/**
	 * Returns user email address.
	 * @return string
	 */
	public function getEmail();
}

