<?php

require(Yii::getPathOfAlias('usr.extensions').DIRECTORY_SEPARATOR.'password.php');

/**
 * This is the model class for table "{{user_used_passwords}}".
 *
 * The followings are the available columns in table '{{user_used_passwords}}':
 * @property integer $id
 * @property integer $user_id
 * @property string $password
 * @property string $set_on
 *
 * The followings are the available model relations:
 * @property User $user
 */
abstract class ExampleUserUsedPassword extends CActiveRecord
{
	public function tableName()
	{
		return '{{user_used_passwords}}';
	}

	public function rules()
	{
		return array(
		);
	}

	public function relations()
	{
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	public function attributeLabels()
	{
		return array(
			'id' => Yii::t('models', 'ID'),
			'user_id' => Yii::t('models', 'User'),
			'password' => Yii::t('models', 'Password'),
			'set_on' => Yii::t('models', 'Password Set On'),
		);
	}

	/**
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('user_id',$this->user_id);
		//$criteria->compare('password',$this->password,true);
		$criteria->compare('set_on',$this->set_on,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * @param string $className active record class name.
	 * @return UserUsedPassword the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function verifyPassword($password)
	{
		return $this->password !== null && password_verify($password, $this->password);
	}
}
