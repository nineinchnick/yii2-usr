<?php

class m130703_104658_users_add_one_time_password extends CDbMigration
{
	public function safeUp()
	{
		$this->addColumn('{{users}}', 'one_time_password_secret', 'string');
		$this->addColumn('{{users}}', 'one_time_password_code', 'string');
		$this->addColumn('{{users}}', 'one_time_password_counter', 'integer NOT NULL DEFAULT 0');
	}

	public function safeDown()
	{
		$this->dropColumn('{{users}}', 'one_time_password_counter');
		$this->dropColumn('{{users}}', 'one_time_password_code');
		$this->dropColumn('{{users}}', 'one_time_password_secret');
	}
}

