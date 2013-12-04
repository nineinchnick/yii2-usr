<?php

interface IHybridauthIdentity
{
	/**
	 * Loads a specific user identity connected to specified provider by an identifier.
	 * @param string $provider
	 * @param string $identifier
	 * @return object a user identity object or null if not found.
	 */
	public static function findByProvider($provider, $identifier);

	/**
	 * Associates this identity with a remote one identified by a provider name and identifier.
	 * @param string $provider
	 * @param string $identifier
	 * @return boolean
	 */
	public function addRemoteIdentity($provider, $identifier);
}

