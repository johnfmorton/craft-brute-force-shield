<?php
/**
 * Brute Force Shield plugin for Craft CMS 5.x
 *
 * @link      https://supergeekery.com
 * @copyright Copyright (c) 2024 John F Morton
 */

declare(strict_types=1);

namespace johnfmorton\bruteforceshield\models;

use craft\base\Model;
use craft\helpers\App;

/**
 * Brute Force Shield Settings Model
 *
 * All settings support environment variable references using the $ENV_VAR syntax.
 * For example, you can set the Pushover API Token to "$PUSHOVER_API_TOKEN" in the
 * settings and define the actual value in your .env file.
 *
 * Use the getParsed*() methods to get resolved values.
 *
 * @author    John F Morton
 * @package   BruteForceShield
 * @since     1.0.0
 */
class Settings extends Model
{
    /**
     * Whether protection is enabled (supports $ENV_VAR syntax)
     */
    public string|bool $enabled = true;

    /**
     * Maximum failed attempts before blocking (supports $ENV_VAR syntax)
     */
    public string|int $maxAttempts = 5;

    /**
     * Time window in seconds for counting attempts (supports $ENV_VAR syntax)
     */
    public string|int $attemptWindow = 900;

    /**
     * Block duration in seconds (supports $ENV_VAR syntax)
     */
    public string|int $lockoutDuration = 86400;

    /**
     * IP addresses that should never be blocked
     */
    private array $_whitelistedIps = [];

    /**
     * Get whitelisted IPs
     */
    public function getWhitelistedIps(): array
    {
        return $this->_whitelistedIps;
    }

    /**
     * Set whitelisted IPs - handles editable table format conversion
     */
    public function setWhitelistedIps(array|string $value): void
    {
        // Handle JSON string from database
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        $processed = [];
        foreach ($value as $row) {
            if (is_array($row) && isset($row['ip']) && !empty($row['ip'])) {
                $processed[] = $row['ip'];
            } elseif (is_string($row) && !empty($row)) {
                $processed[] = $row;
            }
        }
        $this->_whitelistedIps = $processed;
    }

    /**
     * Message shown to blocked IPs (supports $ENV_VAR syntax)
     */
    public string $blockMessage = 'Access temporarily blocked due to too many failed login attempts. Please try again later.';

    /**
     * Whether to send notifications when an IP is blocked (supports $ENV_VAR syntax)
     */
    public string|bool $notifyOnBlock = false;

    /**
     * Email address for notifications (supports $ENV_VAR syntax)
     */
    public string $notifyEmail = '';

    /**
     * Whether to protect front-end login forms (supports $ENV_VAR syntax, enabled by default)
     */
    public string|bool $protectFrontEndLogin = true;

    /**
     * Whether Pushover notifications are enabled (supports $ENV_VAR syntax)
     */
    public string|bool $pushoverEnabled = false;

    /**
     * Pushover user key (supports $ENV_VAR syntax)
     */
    public string $pushoverUserKey = '';

    /**
     * Pushover API token (supports $ENV_VAR syntax)
     */
    public string $pushoverApiToken = '';

    // =========================================================================
    // Parsed Getter Methods - Use these to get resolved values
    // =========================================================================

    /**
     * Get the parsed enabled setting (resolves environment variables)
     */
    public function getEnabledParsed(): bool
    {
        $value = App::parseEnv((string)$this->enabled);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the parsed max attempts (resolves environment variables)
     */
    public function getMaxAttemptsParsed(): int
    {
        $value = App::parseEnv((string)$this->maxAttempts);
        return (int)$value;
    }

    /**
     * Get the parsed attempt window (resolves environment variables)
     */
    public function getAttemptWindowParsed(): int
    {
        $value = App::parseEnv((string)$this->attemptWindow);
        return (int)$value;
    }

    /**
     * Get the parsed lockout duration (resolves environment variables)
     */
    public function getLockoutDurationParsed(): int
    {
        $value = App::parseEnv((string)$this->lockoutDuration);
        return (int)$value;
    }

    /**
     * Get the parsed block message (resolves environment variables)
     */
    public function getBlockMessageParsed(): string
    {
        return App::parseEnv($this->blockMessage);
    }

    /**
     * Get the parsed notify on block setting (resolves environment variables)
     */
    public function getNotifyOnBlockParsed(): bool
    {
        $value = App::parseEnv((string)$this->notifyOnBlock);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the parsed notification email (resolves environment variables)
     */
    public function getNotifyEmailParsed(): string
    {
        return App::parseEnv($this->notifyEmail);
    }

    /**
     * Get the parsed protect front-end login setting (resolves environment variables)
     */
    public function getProtectFrontEndLoginParsed(): bool
    {
        $value = App::parseEnv((string)$this->protectFrontEndLogin);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the parsed Pushover enabled setting (resolves environment variables)
     */
    public function getPushoverEnabledParsed(): bool
    {
        $value = App::parseEnv((string)$this->pushoverEnabled);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the parsed Pushover user key (resolves environment variables)
     */
    public function getPushoverUserKeyParsed(): string
    {
        return App::parseEnv($this->pushoverUserKey);
    }

    /**
     * Get the parsed Pushover API token (resolves environment variables)
     */
    public function getPushoverApiTokenParsed(): string
    {
        return App::parseEnv($this->pushoverApiToken);
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'whitelistedIps';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return [
            [['enabled', 'notifyOnBlock', 'pushoverEnabled', 'protectFrontEndLogin'], 'safe'], // Allow string or bool
            [['maxAttempts', 'attemptWindow', 'lockoutDuration'], 'safe'], // Allow string or int
            [['blockMessage', 'notifyEmail', 'pushoverUserKey', 'pushoverApiToken'], 'string'],
            [['whitelistedIps'], 'each', 'rule' => ['string']],
        ];
    }
}
