<?php

interface IPasswordHistoryIdentity
{
	/**
	 * Returns the date when specified password was last set or null if it was never used before.
	 * If null is passed, returns date of setting current password.
	 * @return string date in YYYY-MM-DD format or null if password was never used.
	 */
	public function getPasswordDate($password = null);
	/**
	 * Changes the password and updates last password change date.
	 * Saves old password so it couldn't be used again.
	 * @return boolean
	 */
	public function resetPassword($password);
}

