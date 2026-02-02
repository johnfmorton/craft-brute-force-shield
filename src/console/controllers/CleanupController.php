<?php
/**
 * Login Lockdown plugin for Craft CMS 5.x
 *
 * @link      https://supergeekery.com
 * @copyright Copyright (c) 2024 John F Morton
 */

declare(strict_types=1);

namespace johnfmorton\loginlockdown\console\controllers;

use Craft;
use craft\console\Controller;
use johnfmorton\loginlockdown\LoginLockdown;
use yii\console\ExitCode;

/**
 * Cleanup old login attempt records and expired blocks.
 *
 * @author John F Morton
 * @since 1.1.0
 */
class CleanupController extends Controller
{
    /**
     * @var int Number of days to keep records. Records older than this will be deleted.
     */
    public int $days = 30;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'days';

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        return [
            'd' => 'days',
        ];
    }

    /**
     * Cleanup old login attempt records and expired blocks.
     *
     * Example usage:
     * ```
     * # Delete records older than 30 days (default)
     * php craft login-lockdown/cleanup
     *
     * # Delete records older than 7 days
     * php craft login-lockdown/cleanup --days=7
     * php craft login-lockdown/cleanup -d 7
     * ```
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->stdout("Running Login Lockdown cleanup...\n");

        $deleted = LoginLockdown::$plugin->protectionService->cleanup($this->days);

        $this->stdout("Cleanup complete. Deleted {$deleted} records older than {$this->days} days.\n");

        return ExitCode::OK;
    }
}
