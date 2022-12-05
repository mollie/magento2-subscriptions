<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface SubscriptionToProductInterface extends ExtensibleDataInterface
{
    const ENTITY_ID = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const SUBSCRIPTION_ID = 'subscription_id';
    const PRODUCT_ID = 'product_id';
    const STORE_ID = 'store_id';
    const HAS_PRICE_UPDATE = 'has_price_update';
    const NEXT_PAYMENT_DATE = 'next_payment_date';
    const LAST_REMINDER_DATE = 'last_reminder_date';

    /**
     * Get entity_id
     * @return int|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param int $entityId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setEntityId(int $entityId);

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId(): string;

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setCustomerId(string $customerId);

    /**
     * Get subscription_id
     * @return string|null
     */
    public function getSubscriptionId(): string;

    /**
     * Set subscription_id
     * @param string $subscriptionId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setSubscriptionId(string $subscriptionId);

    /**
     * Get product_id
     * @return int|null
     */
    public function getProductId(): int;

    /**
     * Set product_id
     * @param int $productId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setProductId(int $productId);

    /**
     * Get store_id
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Set store_id
     * @param int $storeId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setStoreId(int $storeId);

    /**
     * Get has_price_update
     * @return int|null
     */
    public function getHasPriceUpdate(): int;

    /**
     * Set has_price_update
     * @param int $hasPriceUpdate
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setHasPriceUpdate(int $hasPriceUpdate);

    /**
     * Get next_payment_date
     * @return string|null
     */
    public function getNextPaymentDate(): ?string;

    /**
     * Set next_payment_date
     * @param string $nextPaymentDate
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setNextPaymentDate(string $nextPaymentDate);

    /**
     * Get last_reminder_date
     * @return string|null
     */
    public function getLastReminderDate(): ?string;

    /**
     * Set last_reminder_date
     * @param string|null $lastReminderDate
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setLastReminderDate(?string $lastReminderDate);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Mollie\Subscriptions\Api\Data\SubscriptionToProductExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mollie\Subscriptions\Api\Data\SubscriptionToProductExtensionInterface $extensionAttributes
    );
}

