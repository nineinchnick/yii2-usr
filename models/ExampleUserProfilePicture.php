<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * This is the model class for table "{{user_profile_pictures}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $original_picture_id
 * @property string $filename
 * @property integer $width
 * @property integer $height
 * @property string $mimetype
 * @property string $created_on
 * @property string $contents
 *
 * The followings are the available model relations:
 * @property UserProfilePicture $originalPicture
 * @property UserProfilePicture[] $thumbnails
 * @property User $user
 */
abstract class ExampleUserProfilePicture extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_profile_pictures}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
    }

    public function getOriginalPicture()
    {
        return $this->hasOne(\app\models\UserProfilePicture::className(), ['id' => 'original_picture_id']);
    }

    public function getThumbnails()
    {
        return $this->hasMany(\app\models\UserProfilePicture::className(), ['original_picture_id' => 'id']);
    }

    public function getUser()
    {
        return $this->hasOne(\app\models\User::className(), ['id' => 'user_id']);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('models', 'ID'),
            'user_id' => Yii::t('models', 'User'),
            'original_picture_id' => Yii::t('models', 'Original Picture'),
            'filename' => Yii::t('models', 'Filename'),
            'width' => Yii::t('models', 'Width'),
            'height' => Yii::t('models', 'Height'),
            'mimetype' => Yii::t('models', 'Mimetype'),
            'created_on' => Yii::t('models', 'Created On'),
            'contents' => Yii::t('models', 'Contents'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_on = date('Y-m-d H:i:s');
        }

        return parent::beforeSave($insert);
    }
}
