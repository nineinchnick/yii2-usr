<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * This is the model class for table "{{user_profile_pictures}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $password
 * @property string $set_on
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

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getOriginalPicture()
    {
        return $this->hasOne(UserProfilePicture::className(), ['id' => 'original_picture_id']);
    }

    public function getThumbnails()
    {
        return $this->hasMany(UserProfilePicture::className(), ['original_picture_id' => 'id']);
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
