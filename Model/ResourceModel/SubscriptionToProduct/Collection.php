<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Model\ResourceModel\SubscriptionToProduct;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'subscription_to_product_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Mollie\Subscriptions\Model\SubscriptionToProduct::class,
            \Mollie\Subscriptions\Model\ResourceModel\SubscriptionToProduct::class
        );
    }
}

