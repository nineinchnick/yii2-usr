<?php

use yii\db\Schema;

class m130701_104658_create_table_users extends \yii\db\Migration
{
	public function safeUp()
	{
		$this->createTable('{{%users}}', array(
			'id'=>'pk',
			'username'=>'string NOT NULL',
			'password'=>'string NOT NULL',
			'email'=>'string NOT NULL',
			'firstname'=>'string',
			'lastname'=>'string',
			'activation_key'=>'string',
			'created_on'=>'timestamp',
			'updated_on'=>'timestamp',
			'last_visit_on'=>'timestamp',
			'password_set_on'=>'timestamp',
			'email_verified'=>'boolean NOT NULL DEFAULT FALSE',
			'is_active'=>'boolean NOT NULL DEFAULT FALSE',
			'is_disabled'=>'boolean NOT NULL DEFAULT FALSE',
		));
		$prefix = $this->db->tablePrefix;
		$this->createIndex($prefix.'users_username_idx', '{{%users}}', 'username', true);
		$this->createIndex($prefix.'users_email_idx', '{{%users}}', 'email', true);
		$this->createIndex($prefix.'users_email_verified_idx', '{{%users}}', 'email_verified');
		$this->createIndex($prefix.'users_is_active_idx', '{{%users}}', 'is_active');
		$this->createIndex($prefix.'users_is_disabled_idx', '{{%users}}', 'is_disabled');
	}

	public function safeDown()
	{
		$this->dropTable('{{%users}}');
	}
}
