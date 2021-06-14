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
     * @var SubscriptionToProductInterfaceFactory
     */
    protected $subscriptionToProductDataFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var string
     */
    protected $_eventPrefix = 'mollie_subscriptions_subscription_to_product';

    public function __construct(
        Context $context,
        Registry $registry,
        SubscriptionToProductInterfaceFactory $subscriptionToProductDataFactory,
        DataObjectHelper $dataObjectHelper,
        ResourceModel\SubscriptionToProduct $resource,
        Collection $resourceCollection,
        array $data = []
    ) {
        $this->subscriptionToProductDataFactory = $subscriptionToProductDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve subscription_to_product model with subscription_to_product data
     * @return SubscriptionToProductInterface
     */
    public function getDataModel()
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

