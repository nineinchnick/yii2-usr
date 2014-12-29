<?php

namespace nineinchnick\usr\components;

interface PictureIdentityInterface
{
    /**
     * Saves an uploaded picture. This method can be left unimplemented (throw an exception) to disable image upload,
     * just remember to remove any rules from the nineinchnick\usr\Module::$pictureUploadRules module property.
     * Although the $picture argument is an instance of CUploadedFile it could be created manually, so the implementation
     * shouldn't call saveAs() method. Copy the file manually from the path obtained by getTempName().
     * @param  CUploadedFile $picture
     * @return boolean
     */
    public function savePicture($picture);

    /**
     * Returns an URL to the profile picture and the actual width and height of the picture it points to.
     * It may be an url to the profilePicture action in the default controller or some external service
     * like Gravatar or one of the social sites that this identity is associated with.
     * @see DefaultController::actionProfilePicture()
     * @param  integer $width  maximum width, if null, gets the biggest picture
     * @param  integer $height maximum height, if null, gets the biggest picture
     * @return array   with keys: url, width, height
     */
    public function getPictureUrl($width = null, $height = null);

    /**
     * Returns a picture with some metadata like dimensions and mimetype.
     * @param  string  $id
     * @param  boolean $currentIdentity if true, only pictures for the current identity will be returned
     * @return array   with keys: mimetype, width, height, picture
     */
    public function getPicture($id, $currentIdentity = true);

    /**
     * Removes one or all profile pictures.
     * @param  string  $id if null, removes all profile pictures
     * @return integer number of pictures removed
     */
    public function removePicture($id = null);
}
