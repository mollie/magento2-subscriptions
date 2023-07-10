<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Config repository class
 */
class Config
{
    const EXTENSION_CODE = 'Mollie_Subscriptions';
    const XML_PATH_DEBUG_ERROR_EMAIL_TEMPLATE = 'mollie_subscriptions/debug/error_email_template';
    const XML_PATH_DEBUG_ENABLE_ERROR_EMAILS = 'mollie_subscriptions/debug/enable_error_emails';
    const XML_PATH_DEBUG_ERROR_SENDER_EMAIL = 'mollie_subscriptions/debug/error_sender_email';
    const XML_PATH_DEBUG_ERROR_RECEIVER_EMAIL = 'mollie_subscriptions/debug/error_receiver_email';
    const XML_PATH_EXTENSION_VERSION = 'mollie_subscriptions/general/version';
    const XML_PATH_EXTENSION_ENABLE = 'mollie_subscriptions/general/enable';
    const XML_PATH_EXTENSION_SHIPPING_METHOD = 'mollie_subscriptions/general/shipping_method';
    const XML_PATH_DEBUG = 'mollie_subscriptions/general/debug';
    const XML_PATH_PREPAYMENT_REMINDER_DAYS_BEFORE_REMINDER = 'mollie_subscriptions/prepayment_reminder/days_before_reminder';
    const XML_PATH_PREPAYMENT_REMINDER_ENABLED = 'mollie_subscriptions/prepayment_reminder/enabled';
    const XML_PATH_PREPAYMENT_REMINDER_TEMPLATE = 'mollie_subscriptions/prepayment_reminder/template';
    const XML_PATH_PREPAYMENT_REMINDER_SEND_BCC_TO = 'mollie_subscriptions/prepayment_reminder/send_bcc_to';
    const XML_PATH_EMAILS_ENABLE_ADMIN_NOTIFICATION = 'mollie_subscriptions/emails/enable_admin_notification';
    const XML_PATH_EMAILS_ADMIN_NOTIFICATION_TEMPLATE = 'mollie_subscriptions/emails/admin_notification_template';
    const XML_PATH_EMAILS_ENABLE_CUSTOMER_NOTIFICATION = 'mollie_subscriptions/emails/enable_customer_notification';
    const XML_PATH_EMAILS_CUSTOMER_NOTIFICATION_TEMPLATE = 'mollie_subscriptions/emails/customer_notification_template';
    const XML_PATH_EMAILS_ENABLE_ADMIN_RESTART_NOTIFICATION = 'mollie_subscriptions/emails/enable_admin_restart_notification';
    const XML_PATH_EMAILS_ADMIN_RESTART_NOTIFICATION_TEMPLATE = 'mollie_subscriptions/emails/admin_restart_notification_template';
    const XML_PATH_EMAILS_ENABLE_CUSTOMER_RESTART_NOTIFICATION = 'mollie_subscriptions/emails/enable_customer_restart_notification';
    const XML_PATH_EMAILS_CUSTOMER_RESTART_NOTIFICATION_TEMPLATE = 'mollie_subscriptions/emails/customer_restart_notification_template';
    const XML_PATH_EMAILS_ENABLE_ADMIN_CANCEL_NOTIFICATION = 'mollie_subscriptions/emails/enable_admin_cancel_notification';
    const XML_PATH_EMAILS_ADMIN_CANCEL_NOTIFICATION_TEMPLATE = 'mollie_subscriptions/emails/admin_cancel_notification_template';
    const XML_PATH_EMAILS_ENABLE_CUSTOMER_CANCEL_NOTIFICATION = 'mollie_subscriptions/emails/enable_customer_cancel_notification';
    const XML_PATH_EMAILS_CUSTOMER_CANCEL_NOTIFICATION_TEMPLATE = 'mollie_subscriptions/emails/customer_cancel_notification_template';
    const XML_PATH_DISABLE_NEW_ORDER_CONFIRMATION = 'mollie_subscriptions/emails/disable_new_order_confirmation';
    const XML_PATH_ALLOW_ONE_TIME_PURCHASE = 'mollie_subscriptions/general/allow_one_time_purchases';
    const MODULE_SUPPORT_LINK = 'https://www.magmodules.eu/help/%s';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProductMetadataInterface
     */
    private $metadata;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ProductMetadataInterface $metadata
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->metadata = $metadata;
    }

    /**
     * Get Configuration data
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     *
     * @return string
     */
    private function getStoreValue(
        string $path,
        $storeId = null,
        string $scope = null
    ): string {
        if (!$storeId) {
            $storeId = (int)$this->getStore()->getId();
        }
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return (string)$this->scopeConfig->getValue($path, $scope, (int)$storeId);
    }

    /**
     * @return string
     */
    public function getExtensionVersion(): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_VERSION);
    }

    /**
     * @return StoreInterface
     */
    public function getStore(): StoreInterface
    {
        try {
            return $this->storeManager->getStore();
        } catch (Exception $e) {
            if ($store = $this->storeManager->getDefaultStoreView()) {
                return $store;
            }
        }
        $stores = $this->storeManager->getStores();
        return reset($stores);
    }

    /**
     * @return string
     */
    public function getMagentoVersion(): string
    {
        return $this->metadata->getVersion();
    }

    /**
     * Get config value flag
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     *
     * @return bool
     */
    private function getFlag(string $path, int $storeId = null, string $scope = null): bool
    {
        if (!$storeId) {
            $storeId = (int)$this->getStore()->getId();
        }
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->isSetFlag($path, $scope, (int)$storeId);
    }

    /**
     * @return string
     */
    public function getExtensionCode(): string
    {
        return self::EXTENSION_CODE;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_EXTENSION_ENABLE, $storeId);
    }

    /**
     * @param int $storeId
     * @param string $scope
     * @return bool
     */
    public function isErrorEmailEnabled(int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(self::XML_PATH_DEBUG_ENABLE_ERROR_EMAILS, $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string
     */
    public function errorEmailSender(int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->getStoreValue(self::XML_PATH_DEBUG_ERROR_SENDER_EMAIL, $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string
     */
    public function errorEmailReceiver(int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->getStoreValue(self::XML_PATH_DEBUG_ERROR_RECEIVER_EMAIL, $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getShippingMethod(int $storeId = null): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_SHIPPING_METHOD, $storeId);
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink(): string
    {
        return sprintf(
            self::MODULE_SUPPORT_LINK,
            $this->getExtensionCode()
        );
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return bool
     */
    public function isPrepaymentReminderEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_PREPAYMENT_REMINDER_ENABLED, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function prepaymentReminderTemplate($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_PREPAYMENT_REMINDER_TEMPLATE, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function daysBeforePrepaymentReminder($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_PREPAYMENT_REMINDER_DAYS_BEFORE_REMINDER, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function prepaymentSendBccTo($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_PREPAYMENT_REMINDER_SEND_BCC_TO, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return bool
     */
    public function allowOneTimePurchase($storeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_ALLOW_ONE_TIME_PURCHASE, $storeId, $scope);
    }

    /**
     * @param int $storeId
     * @param string $scope
     * @return null|string
     */
    public function subscriptionErrorAdminNotificationTemplate(int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_DEBUG_ERROR_EMAIL_TEMPLATE, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return bool
     */
    public function enableAdminNotificationEmail($storeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_ADMIN_NOTIFICATION, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function getAdminNotificationTemplate($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_ADMIN_NOTIFICATION_TEMPLATE, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return bool
     */
    public function enableCustomerNotificationEmail($storeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_CUSTOMER_NOTIFICATION, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function getCustomerNotificationTemplate($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_CUSTOMER_NOTIFICATION_TEMPLATE, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return bool
     */
    public function enableAdminRestartNotificationEmail($storeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_ADMIN_RESTART_NOTIFICATION, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function getAdminRestartNotificationTemplate($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_ADMIN_RESTART_NOTIFICATION_TEMPLATE, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return bool
     */
    public function enableCustomerRestartNotificationEmail($storeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_CUSTOMER_RESTART_NOTIFICATION, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function getCustomerRestartNotificationTemplate($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_CUSTOMER_RESTART_NOTIFICATION_TEMPLATE, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return bool
     */
    public function enableAdminCancelNotificationEmail($storeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_ADMIN_CANCEL_NOTIFICATION, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function getAdminCancelNotificationTemplate($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_ADMIN_CANCEL_NOTIFICATION_TEMPLATE, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return bool
     */
    public function enableCustomerCancelNotificationEmail($storeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_CUSTOMER_CANCEL_NOTIFICATION, $storeId, $scope);
    }

    /**
     * @param null|int|string $storeId
     * @param string $scope
     * @return string|null
     */
    public function getCustomerCancelNotificationTemplate($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_CUSTOMER_CANCEL_NOTIFICATION_TEMPLATE, $storeId, $scope);
    }

    /**
     * @param $storeId
     * @param $scope
     * @return bool
     */
    public function disableNewOrderConfirmation($storeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_DISABLE_NEW_ORDER_CONFIRMATION, $storeId, $scope);
    }
}
