<?php

/**
 * This is the model class for table "{{user_remote_identities}}".
 *
 * The followings are the available columns in table '{{user_remote_identities}}':
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
abstract class ExampleUserRemoteIdentity extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{user_remote_identities}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('user_id, provider, identifier', 'required'),
			array('user_id', 'numerical', 'integerOnly'=>true),
			array('provider, identifier', 'length', 'max'=>100),
			array('user_id', 'isUnique'),
		);
	}

	public function isUnique($attribute, $params)
	{
		return 0 === $this->countByAttributes(array(
			'user_id'=>$this->user_id,
			'provider'=>$this->provider,
			'identifier'=>$this->identifier,
		));
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => 'User',
			'provider' => 'Provider',
			'identifier' => 'Identifier',
			'created_on' => 'Created On',
			'last_used_on' => 'Last Used On',
		);
	}

	/**
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('provider',$this->provider,true);
		$criteria->compare('identifier',$this->identifier,true);
		//$criteria->compare('created_on',$this->created_on,true);
		//$criteria->compare('last_used_on',$this->last_used_on,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * @param string $className active record class name.
	 * @return UserRemoteIdentity the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	protected function beforeSave()
	{
		if ($this->isNewRecord) {
			$this->created_on = date('Y-m-d H:i:s');
		}
		return parent::beforeSave();
	}
}
