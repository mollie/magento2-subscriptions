<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Api;

interface SubscriptionToProductRepositoryInterface
{
    /**
     * Save subscription_to_product
     * @param \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface $subscriptionToProduct
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface $subscriptionToProduct
    );

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function productHasPriceUpdate(\Magento\Catalog\Api\Data\ProductInterface $product): bool;

    /**
     * Retrieve subscription_to_product
     * @param string $subscriptionToProductId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($subscriptionToProductId);

    /**
     * Retrieve subscription_to_product
     * @param string $subscriptionId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function getBySubscriptionId(string $subscriptionId);

    /**
     * Retrieve subscription_to_product with a price update
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubscriptionsWithAPriceUpdate();

    /**
     * Retrieve subscription_to_product matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Retrieve subscription_to_product matching the $productId.
     * @param int $productId
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByProductId(int $productId);

    /**
     * Retrieve subscription_to_product matching the $customerId and $productId.
     * @param string $mollieCustomerId
     * @param int $productId
     * @throws \Magento\Framework\Exception\NotFoundException
     *@return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface
     */
    public function getByCustomerIdAndProductId(string $mollieCustomerId, int $productId);

    /**
     * Delete subscription_to_product
     * @param \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface $subscriptionToProduct
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface $subscriptionToProduct
    );

    /**
     * Delete subscription_to_product by the subscription id
     * @param string $customerId
     * @param string $subscriptionId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteBySubscriptionId(string $customerId, string $subscriptionId): bool;

    /**
     * Delete subscription_to_product by ID
     * @param string $entityId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return bool true on success
     */
    public function deleteById($entityId);
}

