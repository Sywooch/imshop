<?php

use yii\db\Schema;
use yii\db\Migration;

class m150708_151440_create_users_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // Users table
        $this->createTable('{{%users}}', [
            'id' => Schema::TYPE_PK,
            'username' => Schema::TYPE_STRING . '(100) NOT NULL',
            'password_hash' => Schema::TYPE_STRING . ' NOT NULL',
            'password_reset_token' => Schema::TYPE_STRING,
            'auth_key' => Schema::TYPE_STRING . '(32) NOT NULL',
            'access_token' => Schema::TYPE_STRING . ' NOT NULL',
            'email' => Schema::TYPE_STRING . ' NOT NULL',
            'role' => Schema::TYPE_STRING . ' NOT NULL DEFAULT "user"',
            'status' => 'tinyint(1) NOT NULL DEFAULT 1',
            'registration_ip' => Schema::TYPE_BIGINT . ' NOT NULL',
            'last_login_ip' => Schema::TYPE_BIGINT . ' NOT NULL',
            'created_at' => Schema::TYPE_INTEGER . ' NOT NULL',
            'updated_at' => Schema::TYPE_INTEGER . ' NOT NULL',
            'confirmed_at' => Schema::TYPE_INTEGER . ' NOT NULL',
            'last_login_at' => Schema::TYPE_INTEGER . ' NOT NULL',
            'blocked_at' => Schema::TYPE_INTEGER . ' NOT NULL'
        ], $tableOptions);

        // Indexes
        $this->createIndex('username', '{{%users}}', 'username', true);
        $this->createIndex('email', '{{%users}}', 'email', true);
        $this->createIndex('access_token', '{{%users}}', 'access_token');
        $this->createIndex('password_reset_token', '{{%users}}', 'password_reset_token');
        $this->createIndex('role', '{{%users}}', 'role');
        $this->createIndex('status', '{{%users}}', 'status');

        // Users profiles table
        $this->createTable(
            '{{%profiles}}',
            [
                'id' => Schema::TYPE_PK,
                'user_id' => Schema::TYPE_INTEGER,
                'first_name' => Schema::TYPE_STRING . '(100) NOT NULL',
                'last_name' => Schema::TYPE_STRING . '(100) NOT NULL',
                'avatar_url' => Schema::TYPE_STRING . '(100) NOT NULL'
            ],
            $tableOptions
        );

        // Foreign Keys
        $this->addForeignKey('FK_profiles_user_id', '{{%profiles}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');

        // Add super-administrator
        $this->execute($this->getUserSql());
        $this->execute($this->getProfileSql());
    }

    /**
     * @return string SQL to insert first user
     */
    private function getUserSql()
    {
        $time = time();
        $password_hash = Yii::$app->security->generatePasswordHash('admin12345');
        $auth_key = Yii::$app->security->generateRandomString();
        $token = Yii::$app->security->generateRandomString() . '_' . time();
        return "INSERT INTO {{%users}} (`username`, `email`, `password_hash`, `auth_key`, `password_reset_token`, `role`, `status`, `created_at`, `updated_at`) VALUES ('admin', 'admin@demo.com', '$password_hash', '$auth_key', '$token', 'superadmin', 1, $time, $time)";
    }

    /**
     * @return string SQL to insert first profile
     */
    private function getProfileSql()
    {
        return "INSERT INTO {{%profiles}} (`user_id`, `first_name`, `last_name`, `avatar_url`) VALUES (1, 'Admin', 'Admin', '')";
    }

    public function safeDown()
    {
        $this->dropTable('{{%profiles}}');
        $this->dropTable('{{%users}}');
    }
}