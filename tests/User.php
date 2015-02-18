<?php

namespace nineinchnick\usr\tests;

class User extends \nineinchnick\usr\models\ExampleUser
{
    public function getUserRemoteIdentities()
    {
        return $this->hasMany(UserRemoteIdentity::className(), ['user_id' => 'id']);
    }

    public function getUserUsedPasswords()
    {
        return $this->hasMany(UserUsedPassword::className(), ['user_id' => 'id'])->orderBy('set_on DESC');
    }

    public static function withUserRemoteIdentities($query)
    {
        $query->leftJoin(UserRemoteIdentity::tableName(), self::tableName().'.[['.self::primaryKey()[0].']]='.UserRemoteIdentity::tableName().'.[[user_id]]');
    }

    public function resetPassword($password)
    {
        $hashedPassword = \Yii::$app->security->generatePasswordHash($password);
        $usedPassword = new UserUsedPassword();
        $usedPassword->setAttributes([
            'user_id' => $this->id,
            'password' => $hashedPassword,
            'set_on' => date('Y-m-d H:i:s'),
        ], false);
        $this->setAttributes([
            'password' => $hashedPassword,
            'password_set_on' => date('Y-m-d H:i:s'),
        ], false);

        return $usedPassword->save() && $this->save();
    }

    public static function findByProvider($provider, $identifier)
    {
        return self::find()
            ->withUserRemoteIdentities()
            ->andWhere(UserRemoteIdentity::tableName().'.[[provider]]=:provider', [':provider' => $provider])
            ->andWhere(UserRemoteIdentity::tableName().'.[[identifier]]=:identifier', [':identifier' => $identifier])
            ->one();
    }

    public function addRemoteIdentity($provider, $identifier)
    {
        $model = new UserRemoteIdentity();
        $model->setAttributes([
            'user_id' => $this->id,
            'provider' => $provider,
            'identifier' => $identifier,
        ], false);

        return $model->save();
    }
}
