<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterfaceFactory;
use Mollie\Subscriptions\Model\ResourceModel\SubscriptionToProduct\Collection;

class SubscriptionToProduct extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'mollie_subscriptions_subscription_to_product';

    public function __construct(
        Context $context,
        Registry $registry,
        protected readonly SubscriptionToProductInterfaceFactory $subscriptionToProductDataFactory,
        protected readonly DataObjectHelper $dataObjectHelper,
        ResourceModel\SubscriptionToProduct $resource,
        Collection $resourceCollection,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function getDataModel(): SubscriptionToProductInterface
    {
        $subscription_to_productData = $this->getData();
        
        $subscription_to_productDataObject = $this->subscriptionToProductDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $subscription_to_productDataObject,
            $subscription_to_productData,
            SubscriptionToProductInterface::class
        );
        
        return $subscription_to_productDataObject;
    }
}

