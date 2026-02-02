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
use craft\helpers\Console;
use johnfmorton\loginlockdown\LoginLockdown;
use yii\console\ExitCode;

/**
 * Manage blocked IP addresses.
 *
 * @author John F Morton
 * @since 1.1.0
 */
class BlockController extends Controller
{
    /**
     * @var bool Include expired blocks when listing
     */
    public bool $all = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'list') {
            $options[] = 'all';
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        return [
            'a' => 'all',
        ];
    }

    /**
     * List all currently blocked IPs.
     *
     * Example usage:
     * ```
     * # List active blocks only
     * php craft login-lockdown/block/list
     *
     * # Include expired blocks
     * php craft login-lockdown/block/list --all
     * php craft login-lockdown/block/list -a
     * ```
     *
     * @return int
     */
    public function actionList(): int
    {
        $blockedIps = LoginLockdown::$plugin->protectionService->getBlockedIps($this->all);

        if (empty($blockedIps)) {
            $this->stdout("No blocked IPs found.\n");
            return ExitCode::OK;
        }

        $label = $this->all ? "All blocked IPs" : "Currently blocked IPs";
        $this->stdout("{$label}:\n\n");

        $now = new \DateTime();

        foreach ($blockedIps as $record) {
            $blockedUntil = new \DateTime($record->blockedUntil);
            $isExpired = $blockedUntil < $now;
            $status = $isExpired ? '[EXPIRED]' : '[ACTIVE]';

            $this->stdout("  IP: ");
            $this->stdout($record->ipAddress, Console::FG_YELLOW);
            $this->stdout("\n");
            $this->stdout("    Status: {$status}\n");
            $this->stdout("    Reason: {$record->reason}\n");
            $this->stdout("    Attempts: {$record->attemptCount}\n");
            $this->stdout("    Blocked until: {$record->blockedUntil}\n");
            $this->stdout("    Manual block: " . ($record->isManual ? 'Yes' : 'No') . "\n");
            $this->stdout("\n");
        }

        $count = count($blockedIps);
        $this->stdout("Total: {$count} blocked IP(s)\n");

        return ExitCode::OK;
    }

    /**
     * Block an IP address manually.
     *
     * Example usage:
     * ```
     * php craft login-lockdown/block/add 192.168.1.100
     * ```
     *
     * @param string $ipAddress The IP address to block
     * @return int
     */
    public function actionAdd(string $ipAddress): int
    {
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $this->stderr("Invalid IP address: {$ipAddress}\n", Console::FG_RED);
            return ExitCode::USAGE;
        }

        LoginLockdown::$plugin->protectionService->blockIp(
            $ipAddress,
            0,
            'Blocked via CLI',
            true
        );

        $this->stdout("IP address ");
        $this->stdout($ipAddress, Console::FG_YELLOW);
        $this->stdout(" has been blocked.\n");

        return ExitCode::OK;
    }

    /**
     * Unblock an IP address.
     *
     * Example usage:
     * ```
     * php craft login-lockdown/block/remove 192.168.1.100
     * ```
     *
     * @param string $ipAddress The IP address to unblock
     * @return int
     */
    public function actionRemove(string $ipAddress): int
    {
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $this->stderr("Invalid IP address: {$ipAddress}\n", Console::FG_RED);
            return ExitCode::USAGE;
        }

        $unblocked = LoginLockdown::$plugin->protectionService->unblockIp($ipAddress);

        if ($unblocked) {
            $this->stdout("IP address ");
            $this->stdout($ipAddress, Console::FG_YELLOW);
            $this->stdout(" has been unblocked.\n");
        } else {
            $this->stdout("IP address {$ipAddress} was not found in the block list.\n");
        }

        return ExitCode::OK;
    }

    /**
     * Check if an IP address is currently blocked.
     *
     * Example usage:
     * ```
     * php craft login-lockdown/block/check 192.168.1.100
     * ```
     *
     * @param string $ipAddress The IP address to check
     * @return int
     */
    public function actionCheck(string $ipAddress): int
    {
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $this->stderr("Invalid IP address: {$ipAddress}\n", Console::FG_RED);
            return ExitCode::USAGE;
        }

        $isBlocked = LoginLockdown::$plugin->protectionService->isBlocked($ipAddress);
        $isWhitelisted = LoginLockdown::$plugin->protectionService->isWhitelisted($ipAddress);

        $this->stdout("IP: ");
        $this->stdout($ipAddress, Console::FG_YELLOW);
        $this->stdout("\n");

        if ($isWhitelisted) {
            $this->stdout("Status: ", Console::FG_GREEN);
            $this->stdout("WHITELISTED\n", Console::FG_GREEN);
        } elseif ($isBlocked) {
            $this->stdout("Status: ", Console::FG_RED);
            $this->stdout("BLOCKED\n", Console::FG_RED);
        } else {
            $this->stdout("Status: ", Console::FG_GREEN);
            $this->stdout("NOT BLOCKED\n", Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
