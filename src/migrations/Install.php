<?php
/**
 * Login Lockdown plugin for Craft CMS 5.x
 *
 * @link      https://supergeekery.com
 * @copyright Copyright (c) 2024 John F Morton
 */

declare(strict_types=1);

namespace johnfmorton\loginlockdown\migrations;

use Craft;
use craft\db\Migration;
use johnfmorton\loginlockdown\records\BlockedIpRecord;
use johnfmorton\loginlockdown\records\LoginAttemptRecord;

/**
 * Login Lockdown Install Migration
 *
 * @author    John F Morton
 * @package   LoginLockdown
 * @since     1.0.0
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();

        // Refresh the db schema caches
        Craft::$app->db->schema->refresh();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(BlockedIpRecord::tableName());
        $this->dropTableIfExists(LoginAttemptRecord::tableName());

        return true;
    }

    /**
     * Create the database tables
     */
    private function createTables(): void
    {
        // Login attempts table
        if ($this->db->schema->getTableSchema(LoginAttemptRecord::tableName()) === null) {
            $this->createTable(LoginAttemptRecord::tableName(), [
                'id' => $this->primaryKey(),
                'ipAddress' => $this->string(45)->notNull(),
                'username' => $this->string(255)->null(),
                'userAgent' => $this->text()->null(),
                'dateAttempted' => $this->dateTime()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        // Blocked IPs table
        if ($this->db->schema->getTableSchema(BlockedIpRecord::tableName()) === null) {
            $this->createTable(BlockedIpRecord::tableName(), [
                'id' => $this->primaryKey(),
                'ipAddress' => $this->string(45)->notNull(),
                'reason' => $this->string(255)->null(),
                'attemptCount' => $this->integer()->notNull()->defaultValue(0),
                'blockedUntil' => $this->dateTime()->notNull(),
                'isManual' => $this->boolean()->notNull()->defaultValue(false),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }
    }

    /**
     * Create the database indexes
     */
    private function createIndexes(): void
    {
        // Login attempts indexes
        $this->createIndex(
            null,
            LoginAttemptRecord::tableName(),
            ['ipAddress'],
            false
        );

        $this->createIndex(
            null,
            LoginAttemptRecord::tableName(),
            ['dateAttempted'],
            false
        );

        $this->createIndex(
            null,
            LoginAttemptRecord::tableName(),
            ['ipAddress', 'dateAttempted'],
            false
        );

        // Blocked IPs indexes
        $this->createIndex(
            null,
            BlockedIpRecord::tableName(),
            ['ipAddress'],
            true  // Unique index - one record per IP
        );

        $this->createIndex(
            null,
            BlockedIpRecord::tableName(),
            ['blockedUntil'],
            false
        );
    }
}
