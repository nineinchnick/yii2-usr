<?php

namespace nineinchnick\usr\components;

interface ManagedIdentityInterface
{
    const STATUS_EMAIL_VERIFIED = 'email_verified';
    const STATUS_IS_ACTIVE = 'is_active';
    const STATUS_IS_DISABLED = 'is_disabled';
    /**
     * Returns a data provider filled with User identity instances.
     * @param  SearchForm   $searchForm
     * @return DataProvider
     */
    public function getDataProvider(\nineinchnick\usr\models\SearchForm $searchForm);
    /**
     * Toggles email verification, active or disabled status.
     * @param  string  $status on of following consts: self::STATUS_EMAIL_VERIFIED, self::STATUS_IS_ACTIVE, self::STATUS_IS_DISABLED
     * @return boolean
     */
    public function toggleStatus($status);
    /**
     * Removes a user account. Note this could fail if there are constraints set up in the db that prevent
     * removing a user that still has some relations pointing to it.
     * @return boolean
     */
    public function delete();
    /**
     * Retrieves various timestamps, like time of creation, last update, last login.
     * @param  string       $key if not null, returns a single value if one of: createdOn, updatedOn, lastVisitOn
     * @return array|string single date or array with keys: createdOn, updatedOn, lastVisitOn
     */
    public function getTimestamps($key = null);
}
