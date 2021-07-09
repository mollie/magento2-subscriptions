<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Api\Data;

interface SubscriptionToProductSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get subscription_to_product list.
     * @return \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface[]
     */
    public function getItems();

    /**
     * Set entity_id list.
     * @param \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

