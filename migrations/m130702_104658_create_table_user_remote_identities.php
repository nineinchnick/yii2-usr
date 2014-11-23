<?php

class m130702_104658_create_table_user_remote_identities extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_remote_identities}}', [
            'id' => 'pk',
            'user_id' => 'integer NOT NULL REFERENCES {{%users}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
            'provider' => 'varchar(100) NOT NULL',
            'identifier' => 'varchar(100) NOT NULL',
            'created_on' => 'timestamp NOT NULL',
            'last_used_on' => 'timestamp',
        ]);
        $prefix = $this->db->tablePrefix;
        $this->createIndex($prefix.'user_remote_identities_user_id_provider_identifier_idx', '{{%user_remote_identities}}', 'user_id, provider, identifier', true);
        $this->createIndex($prefix.'user_remote_identities_user_id_idx', '{{%user_remote_identities}}', 'user_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user_remote_identities}}');
    }
}
