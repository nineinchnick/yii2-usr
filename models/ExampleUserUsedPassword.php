<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * This is the model class for table "{{user_used_passwords}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $password
 * @property string $set_on
 *
 * The followings are the available model relations:
 * @property User $user
 */
abstract class ExampleUserUsedPassword extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_used_passwords}}';
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
            'password' => Yii::t('models', 'Password'),
            'set_on' => Yii::t('models', 'Password Set On'),
        ];
    }

    /**
     * @param  string $password password to validate
     * @return bool   if password provided is valid for saved one
     */
    public function verifyPassword($password)
    {
        return $this->password !== null && Yii::$app->security->validatePassword($password, $this->password);
    }
}
