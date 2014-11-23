<?php

class m130705_104658_create_table_user_profile_pictures extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_profile_pictures}}', [
            'id' => 'pk',
            'user_id' => 'integer NOT NULL REFERENCES {{%users}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
            'original_picture_id' => 'integer REFERENCES {{%user_profile_pictures}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
            'filename' => 'string NOT NULL',
            'width' => 'integer NOT NULL',
            'height' => 'integer NOT NULL',
            'mimetype' => 'string NOT NULL',
            'created_on' => 'timestamp NOT NULL',
            'contents' => 'text NOT NULL',
        ]);
        $prefix = $this->db->tablePrefix;
        $this->createIndex($prefix.'user_profile_pictures_user_id_idx', '{{%user_profile_pictures}}', 'user_id');
        $this->createIndex($prefix.'user_profile_pictures_original_picture_id_idx', '{{%user_profile_pictures}}', 'original_picture_id');
        $this->createIndex($prefix.'user_profile_pictures_width_height_idx', '{{%user_profile_pictures}}', 'width, height');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user_profile_pictures}}');
    }
}
