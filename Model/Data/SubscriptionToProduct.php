<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;

class SubscriptionToProduct extends AbstractExtensibleObject implements SubscriptionToProductInterface
{
    /**
     * Get entity_id
     * @return int|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param int $entityId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setEntityId(int $entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId(): string
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setCustomerId(string $customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get subscription_id
     * @return string|null
     */
    public function getSubscriptionId(): string
    {
        return $this->_get(self::SUBSCRIPTION_ID);
    }

    /**
     * Set subscription_id
     * @param string $subscriptionId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setSubscriptionId(string $subscriptionId)
    {
        return $this->setData(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    /**
     * Get product_id
     * @return int|null
     */
    public function getProductId(): int
    {
        return $this->_get(self::PRODUCT_ID);
    }

    /**
     * Set product_id
     * @param int $productId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setProductId(int $productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * Get store_id
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        return $this->_get(self::STORE_ID);
    }

    /**
     * Set store_id
     * @param int $storeId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setStoreId(int $storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Get has_price_update
     * @return int|null
     */
    public function getHasPriceUpdate(): int
    {
        return $this->_get(self::HAS_PRICE_UPDATE) ?? 0;
    }

    /**
     * Set has_price_update
     * @param int $hasPriceUpdate
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setHasPriceUpdate(int $hasPriceUpdate)
    {
        return $this->setData(self::HAS_PRICE_UPDATE, $hasPriceUpdate);
    }

    /**
     * Get next_payment_date
     * @return string|null
     */
    public function getNextPaymentDate(): ?string
    {
        return $this->_get(self::NEXT_PAYMENT_DATE);
    }

    /**
     * Set next_payment_date
     * @param string $nextPaymentDate
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setNextPaymentDate(string $nextPaymentDate)
    {
        return $this->setData(self::NEXT_PAYMENT_DATE, $nextPaymentDate);
    }

    /**
     * Get last_reminder_date
     * @return string|null
     */
    public function getLastReminderDate(): ?string
    {
        return $this->_get(self::LAST_REMINDER_DATE);
    }

    /**
     * Set last_reminder_date
     * @param string|null $lastReminderDate
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function setLastReminderDate(?string $lastReminderDate)
    {
        return $this->setData(self::LAST_REMINDER_DATE, $lastReminderDate);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductExtensionInterface|\Magento\Framework\Api\ExtensionAttributesInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Mollie\Subscriptions\Api\Data\SubscriptionToProductExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mollie\Subscriptions\Api\Data\SubscriptionToProductExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

