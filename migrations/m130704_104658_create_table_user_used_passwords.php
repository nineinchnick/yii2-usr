<?php

class m130704_104658_create_table_user_used_passwords extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_used_passwords}}', [
            'id' => 'pk',
            'user_id' => 'integer NOT NULL REFERENCES {{%users}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
            'password' => 'string NOT NULL',
            'set_on' => 'timestamp NOT NULL',
        ]);
        $prefix = $this->db->tablePrefix;
        $this->createIndex($prefix.'user_used_passwords_user_id_idx', '{{%user_used_passwords}}', 'user_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user_used_passwords}}');
    }
}
