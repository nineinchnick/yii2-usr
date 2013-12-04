<?php

class m130701_104658_create_table_users extends CDbMigration
{
	public function safeUp()
	{
		$this->createTable('{{users}}', array(
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
		$this->createIndex('{{users}}_username_idx', '{{users}}', 'username', true);
		$this->createIndex('{{users}}_email_idx', '{{users}}', 'email', true);
		$this->createIndex('{{users}}_email_verified_idx', '{{users}}', 'email_verified');
		$this->createIndex('{{users}}_is_active_idx', '{{users}}', 'is_active');
		$this->createIndex('{{users}}_is_disabled_idx', '{{users}}', 'is_disabled');
	}

	public function safeDown()
	{
		$this->dropTable('{{users}}');
	}
}
