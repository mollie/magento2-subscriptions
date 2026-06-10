<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
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
    const XML_PATH_PREPAYMENT_REMINDER_NEXT_PAYMENT_DATE_FORMAT = 'mollie_subscriptions/prepayment_reminder/next_payment_date_format';
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
    const XML_PATH_EMAILS_ENABLE_ADMIN_FAILURE_NOTIFICATION = 'mollie_subscriptions/emails/enable_admin_failure_notification';
    const XML_PATH_EMAILS_ADMIN_FAILURE_NOTIFICATION_TEMPLATE = 'mollie_subscriptions/emails/admin_failure_notification_template';
    const XML_PATH_DISABLE_NEW_ORDER_CONFIRMATION = 'mollie_subscriptions/emails/disable_new_order_confirmation';
    const XML_PATH_ALLOW_ONE_TIME_PURCHASE = 'mollie_subscriptions/general/allow_one_time_purchases';
    const XML_PATH_SHOW_PRICE_IN_SUBSCRIPTION_BUTTON = 'mollie_subscriptions/general/show_price_in_subscription_button';
    const XML_PATH_UPDATE_SUBSCRIPTION_WHEN_PRICE_CHANGES = 'mollie_subscriptions/general/update_subscription_when_price_changes';
    const MODULE_SUPPORT_LINK = 'https://www.magmodules.eu/help/%s';

    public function __construct(
        private readonly \Mollie\Payment\Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ProductMetadataInterface $metadata
    ) {
    }

    public function addToLog(string $type, $data): void
    {
        if (!$this->getFlag(static::XML_PATH_DEBUG)) {
            return;
        }

        $this->config->addToLog($type, $data);
    }

    private function getStoreValue(
        string $path,
        ?int $storeId = null,
        ?string $scope = null
    ): string {
        if (!$storeId) {
            $storeId = storeId($this->getStore()->getId());
        }
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return (string)$this->scopeConfig->getValue($path, $scope, $storeId);
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

    private function getFlag(string $path, ?int $storeId = null, ?string $scope = null): bool
    {
        if (!$storeId) {
            $storeId = storeId($this->getStore()->getId());
        }
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->isSetFlag($path, $scope, $storeId);
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
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_EXTENSION_ENABLE, storeId($storeId));
    }

    /**
     * @param int $storeId
     * @param string $scope
     * @return bool
     */
    public function isErrorEmailEnabled(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(self::XML_PATH_DEBUG_ENABLE_ERROR_EMAILS, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string
     */
    public function errorEmailSender(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->getStoreValue(self::XML_PATH_DEBUG_ERROR_SENDER_EMAIL, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string
     */
    public function errorEmailReceiver(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->getStoreValue(self::XML_PATH_DEBUG_ERROR_RECEIVER_EMAIL, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getShippingMethod(?int $storeId = null): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_SHIPPING_METHOD, storeId($storeId));
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
     * @param int|null $storeId
     * @param string $scope
     * @return bool
     */
    public function isPrepaymentReminderEnabled(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_PREPAYMENT_REMINDER_ENABLED, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string|null
     */
    public function prepaymentReminderTemplate(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_PREPAYMENT_REMINDER_TEMPLATE, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string|null
     */
    public function daysBeforePrepaymentReminder(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_PREPAYMENT_REMINDER_DAYS_BEFORE_REMINDER, storeId($storeId), $scope);
    }

    public function nextPaymentDateFormat(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->getStoreValue(static::XML_PATH_PREPAYMENT_REMINDER_NEXT_PAYMENT_DATE_FORMAT, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string|null
     */
    public function prepaymentSendBccTo(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_PREPAYMENT_REMINDER_SEND_BCC_TO, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return bool
     */
    public function allowOneTimePurchase(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_ALLOW_ONE_TIME_PURCHASE, storeId($storeId), $scope);
    }

    public function showPriceInSubscriptionButton(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_SHOW_PRICE_IN_SUBSCRIPTION_BUTTON, storeId($storeId), $scope);
    }

    public function updateSubscriptionWhenPriceChanges(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_UPDATE_SUBSCRIPTION_WHEN_PRICE_CHANGES, storeId($storeId), $scope);
    }

    /**
     * @param int $storeId
     * @param string $scope
     * @return null|string
     */
    public function subscriptionErrorAdminNotificationTemplate(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_DEBUG_ERROR_EMAIL_TEMPLATE, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return bool
     */
    public function enableAdminNotificationEmail(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_ADMIN_NOTIFICATION, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string|null
     */
    public function getAdminNotificationTemplate(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_ADMIN_NOTIFICATION_TEMPLATE, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return bool
     */
    public function enableCustomerNotificationEmail(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_CUSTOMER_NOTIFICATION, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string|null
     */
    public function getCustomerNotificationTemplate(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_CUSTOMER_NOTIFICATION_TEMPLATE, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return bool
     */
    public function enableAdminRestartNotificationEmail(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_ADMIN_RESTART_NOTIFICATION, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string|null
     */
    public function getAdminRestartNotificationTemplate(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_ADMIN_RESTART_NOTIFICATION_TEMPLATE, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return bool
     */
    public function enableCustomerRestartNotificationEmail(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_CUSTOMER_RESTART_NOTIFICATION, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string|null
     */
    public function getCustomerRestartNotificationTemplate(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_CUSTOMER_RESTART_NOTIFICATION_TEMPLATE, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return bool
     */
    public function enableAdminCancelNotificationEmail(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_ADMIN_CANCEL_NOTIFICATION, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string|null
     */
    public function getAdminCancelNotificationTemplate(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_ADMIN_CANCEL_NOTIFICATION_TEMPLATE, storeId($storeId), $scope);
    }

    public function enableAdminFailureNotificationEmail(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_ADMIN_FAILURE_NOTIFICATION, storeId($storeId), $scope);
    }

    public function getAdminFailureNotificationTemplate(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_ADMIN_FAILURE_NOTIFICATION_TEMPLATE, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return bool
     */
    public function enableCustomerCancelNotificationEmail(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_EMAILS_ENABLE_CUSTOMER_CANCEL_NOTIFICATION, storeId($storeId), $scope);
    }

    /**
     * @param int|null $storeId
     * @param string $scope
     * @return string|null
     */
    public function getCustomerCancelNotificationTemplate(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getStoreValue(static::XML_PATH_EMAILS_CUSTOMER_CANCEL_NOTIFICATION_TEMPLATE, storeId($storeId), $scope);
    }

    public function disableNewOrderConfirmation(?int $storeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->getFlag(static::XML_PATH_DISABLE_NEW_ORDER_CONFIRMATION, storeId($storeId), $scope);
    }
}
