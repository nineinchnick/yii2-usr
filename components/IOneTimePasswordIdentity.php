<?php

interface IOneTimePasswordIdentity
{
	/**
	 * Returns current secret used to generate one time passwords. If it's null, two step auth is disabled.
	 * @return string
	 */
	public function getOneTimePasswordSecret();

	/**
	 * Sets current secret used to generate one time passwords. If it's null, two step auth is disabled.
	 * @param string $secret
	 * @return boolean
	 */
	public function setOneTimePasswordSecret($secret);

	/**
	 * Returns previously used one time password and value of counter used to generate current one time password, used in counter mode.
	 * @return array array(string, integer) 
	 */
	public function getOneTimePassword();

	/**
	 * Sets previously used one time password and value of counter used to generate current one time password, used in counter mode.
	 * @return boolean
	 */
	public function setOneTimePassword($password, $counter = 0);
}

