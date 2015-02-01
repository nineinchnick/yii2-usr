<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * This is the model class for table "{{user_login_attempts}}".
 *
 * @property integer $id
 * @property string $username
 * @property integer $user_id
 * @property string $performed_on
 * @property boolean $is_successful
 * @property string $session_id
 * @property integer $ipv4
 * @property string $user_agent
 *
 * The followings are the available model relations:
 * @property User $user
 */
abstract class ExampleUserLoginAttempt extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_login_attempts}}';
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
            'username' => Yii::t('models', 'Username'),
            'user_id' => Yii::t('models', 'User'),
            'performed_on' => Yii::t('models', 'Performed On'),
            'is_successful' => Yii::t('models', 'Is Successful'),
            'session_id' => Yii::t('models', 'Session ID'),
            'ipv4' => Yii::t('models', 'IPv4'),
            'user_agent' => Yii::t('models', 'User Agent'),
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            /** @var Request */
            $request = Yii::$app->request;
            $this->performed_on = date('Y-m-d H:i:s');
            $this->session_id = Yii::$app->session->sessionID;
            $this->ipv4 = ip2long($request->userIP);
            $this->user_agent = $request->userAgent;
            if ($this->ipv4 > 0x7FFFFFFF) {
                $this->ipv4 -= (0xFFFFFFFF + 1);
            }
        }

        return parent::beforeSave($insert);
    }

    /**
     * Checks if there are not too many login attempts using specified username in the specified number of seconds until now.
     * @param  string  $username
     * @param  integer $count_limit number of login attempts
     * @param  integer $time_limit  number of seconds
     * @return boolean
     */
    public static function hasTooManyFailedAttempts($username, $count_limit = 5, $time_limit = 1800)
    {
        $since = new DateTime();
        $since->sub(new DateInterval("PT{$time_limit}S"));

        $subquery = Yii::$app->db->createCommand()
            ->select('is_successful')
            ->from(self::tableName())
            ->where('username = :username AND performed_on > :since')
            ->order('performed_on DESC')
            ->limit($count_limit)->getText();

        return $count_limit <= (int) Yii::$app->db->createCommand()
            ->select('COUNT(NOT is_successful OR NULL)')
            ->from("({$subquery}) AS t")
            ->queryScalar([':username' => $username, ':since' => $since->format('Y-m-d H:i:s')]);
    }
}
