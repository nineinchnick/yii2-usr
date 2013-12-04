<?php

Yii::import('vendors.nineinchnick.yii-usr.models.ExampleUser');

class User extends ExampleUser
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
