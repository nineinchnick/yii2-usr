<?php

class m130706_104658_create_table_user_login_attempts extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_login_attempts}}', [
            'id' => 'pk',
            'username' => 'string NOT NULL',
            'user_id' => 'integer REFERENCES {{users}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
            'performed_on' => 'timestamp NOT NULL',
            'is_successful' => 'boolean NOT NULL DEFAULT false',
            'session_id' => 'string',
            'ipv4' => 'integer',
            'user_agent' => 'string',
        ]);
        $prefix = $this->db->tablePrefix;
        $this->createIndex($prefix.'user_login_attempts_user_id_idx', '{{%user_login_attempts}}', 'user_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user_login_attempts}}');
    }
}
