<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * This is the model class for table "{{user_remote_identities}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $provider
 * @property string $identifier
 * @property string $created_on
 * @property string $last_used_on
 *
 * The followings are the available model relations:
 * @property User $user
 */
abstract class ExampleUserRemoteIdentity extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_remote_identities}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'provider', 'identifier'], 'required'],
            ['user_id', 'number', 'integerOnly' => true],
            [['provider', 'identifier'], 'string', 'max' => 100],
            ['user_id', 'isUnique'],
        ];
    }

    /**
     * An inline validator that checkes if there are no existing records
     * with same provider and identifier for specified user.
     * @param  string  $attribute
     * @param  array   $params
     * @return boolean
     */
    public function isUnique($attribute, $params)
    {
        return null === self::find([
            'user_id' => $this->user_id,
            'provider' => $this->provider,
            'identifier' => $this->identifier,
        ]);
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
            'provider' => Yii::t('models', 'Provider'),
            'identifier' => Yii::t('models', 'Identifier'),
            'created_on' => Yii::t('models', 'Created On'),
            'last_used_on' => Yii::t('models', 'Last Used On'),
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
