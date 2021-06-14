<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SubscriptionToProduct extends AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mollie_subscription_to_product', 'entity_id');
    }

    public function deleteBySubscriptionId(string $customerId, string $subscriptionId)
    {
        $this->getConnection()->delete($this->getMainTable(), [
            'customer_id = ?' => $customerId,
            'subscription_id = ?' => $subscriptionId,
        ]);
    }

    public function updateProductHasUpdateFor(int $productId)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['has_price_update' => 1],
            ['product_id = ?' => $productId]
        );
    }
}

