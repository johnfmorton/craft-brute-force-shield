<?php
/**
 * Login Lockdown plugin for Craft CMS 5.x
 *
 * @link      https://supergeekery.com
 * @copyright Copyright (c) 2024 John F Morton
 */

declare(strict_types=1);

namespace johnfmorton\loginlockdown\records;

use craft\db\ActiveRecord;

/**
 * Blocked IP Record
 *
 * @property int $id
 * @property string $ipAddress
 * @property string|null $reason
 * @property int $attemptCount
 * @property string $blockedUntil
 * @property bool $isManual
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 *
 * @author    John F Morton
 * @package   LoginLockdown
 * @since     1.0.0
 */
class BlockedIpRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%loginlockdown_blocked_ips}}';
    }
}
