<?php
/**
 * Brute Force Shield plugin for Craft CMS 5.x
 *
 * @link      https://supergeekery.com
 * @copyright Copyright (c) 2024 John F Morton
 */

declare(strict_types=1);

namespace johnfmorton\bruteforceshield\controllers;

use Craft;
use craft\web\Controller;
use johnfmorton\bruteforceshield\BruteForceShield;
use yii\web\Response;

/**
 * Blocked IPs Controller
 *
 * Handles the CP interface for managing blocked IPs.
 *
 * @author    John F Morton
 * @package   BruteForceShield
 * @since     1.0.0
 */
class BlockedIpsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require admin permission but allow access even when allowAdminChanges is false
        // Managing blocked IPs is an operational task, not a configuration change
        $this->requireAdmin(false);
        return parent::beforeAction($action);
    }

    /**
     * Display the blocked IPs list
     */
    public function actionIndex(): Response
    {
        $blockedIps = BruteForceShield::$plugin->protectionService->getBlockedIps(true);

        return $this->renderTemplate('brute-force-shield/index', [
            'blockedIps' => $blockedIps,
        ]);
    }

    /**
     * Unblock an IP address
     */
    public function actionUnblock(): Response
    {
        $this->requirePostRequest();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        if (BruteForceShield::$plugin->protectionService->unblockById((int)$id)) {
            Craft::$app->getSession()->setNotice(Craft::t('brute-force-shield', 'IP address unblocked.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('brute-force-shield', 'Could not unblock IP address.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Manually block an IP address
     */
    public function actionBlock(): Response
    {
        $this->requirePostRequest();

        $ipAddress = Craft::$app->getRequest()->getRequiredBodyParam('ipAddress');

        // Validate IP address format
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            Craft::$app->getSession()->setError(Craft::t('brute-force-shield', 'Invalid IP address format.'));
            return $this->redirectToPostedUrl();
        }

        BruteForceShield::$plugin->protectionService->blockIp(
            $ipAddress,
            0,
            'Manually blocked by admin',
            true
        );

        Craft::$app->getSession()->setNotice(Craft::t('brute-force-shield', 'IP address blocked.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Clean up old records
     */
    public function actionCleanup(): Response
    {
        $this->requirePostRequest();

        $deleted = BruteForceShield::$plugin->protectionService->cleanup();

        Craft::$app->getSession()->setNotice(
            Craft::t('brute-force-shield', '{count} old records cleaned up.', ['count' => $deleted])
        );

        return $this->redirectToPostedUrl();
    }

    /**
     * Send a test Pushover notification
     */
    public function actionTestPushover(): Response
    {
        $this->requirePostRequest();
        $this->requireCpRequest();

        Craft::info('Brute Force Shield: Test Pushover action called', __METHOD__);

        $result = BruteForceShield::$plugin->notificationService->sendTestPushoverNotification();

        Craft::info('Brute Force Shield: Test Pushover result: ' . json_encode($result), __METHOD__);

        return $this->asJson($result);
    }
}
