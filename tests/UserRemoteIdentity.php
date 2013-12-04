<?php

Yii::import('vendors.nineinchnick.yii-usr.models.ExampleUserRemoteIdentity');

class UserRemoteIdentity extends ExampleUserRemoteIdentity
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
