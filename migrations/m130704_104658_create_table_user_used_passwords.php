<?php

class m130704_104658_create_table_user_used_passwords extends CDbMigration
{
	public function safeUp()
	{
		$this->createTable('{{user_used_passwords}}', array(
			'id'=>'pk',
			'user_id'=>'integer NOT NULL REFERENCES {{users}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
			'password'=>'string NOT NULL',
			'set_on'=>'timestamp NOT NULL',
		));
		$this->createIndex('{{user_used_passwords}}_user_id_idx', '{{user_used_passwords}}', 'user_id');
	}

	public function safeDown()
	{
		$this->dropTable('{{user_used_passwords}}');
	}
}

