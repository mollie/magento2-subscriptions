<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ConvertToSubscriptionsTable implements DataPatchInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Action
     */
    private $productAction;

    public function __construct(
        CollectionFactory $collectionFactory,
        SerializerInterface $serializer,
        Action $productAction
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->serializer = $serializer;
        $this->productAction = $productAction;
    }

    public function apply(): self
    {
        $collection = $this->collectionFactory->create();

        $collection->addAttributeToSelect('mollie_subscription_interval_amount');
        $collection->addAttributeToSelect('mollie_subscription_interval_type');
        $collection->addAttributeToSelect('mollie_subscription_repetition_amount');
        $collection->addAttributeToSelect('mollie_subscription_repetition_type');

        $collection->addAttributeToFilter('mollie_subscription_product', ['eq' => '1']);

        foreach ($collection as $product) {
            $intervalAmount = $product->getData('mollie_subscription_interval_amount');
            $intervalType = $product->getData('mollie_subscription_interval_type');

            $data = [
                'identifier' => uniqid(),
                'title' => __('Every %1 %2', $intervalAmount, $intervalType),
                'interval_amount' => $intervalAmount,
                'interval_type' => $intervalType,
                'repetition_amount' => $product->getData('mollie_subscription_repetition_amount'),
                'repetition_type' => $product->getData('mollie_subscription_repetition_type'),
            ];

            $this->productAction->updateAttributes(
                [$product->getEntityId()],
                ['mollie_subscription_table' => $this->serializer->serialize([$data])],
                0
            );
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            AddSubscriptionsTable::class,
            SubscriptionAttributes::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
