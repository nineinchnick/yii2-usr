<?php

class m130701_104658_create_table_users extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%users}}', [
            'id' => 'pk',
            'username' => 'string NOT NULL',
            'password' => 'string NOT NULL',
            'email' => 'string NOT NULL',
            'firstname' => 'string',
            'lastname' => 'string',
            'auth_key' => 'string',
            'activation_key' => 'string',
            'access_token' => 'string',
            'created_on' => 'timestamp',
            'updated_on' => 'timestamp',
            'last_visit_on' => 'timestamp',
            'password_set_on' => 'timestamp',
            'email_verified' => 'boolean NOT NULL DEFAULT 0',
            'is_active' => 'boolean NOT NULL DEFAULT 0',
            'is_disabled' => 'boolean NOT NULL DEFAULT 0',
        ]);
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
