<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Mollie\Subscriptions\DTO\ProductSubscriptionOption;
use Mollie\Subscriptions\DTO\ProductSubscriptionOptionFactory;

class ParseSubscriptionOptions
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ProductSubscriptionOptionFactory
     */
    private $productSubscriptionOptionFactory;

    public function __construct(
        SerializerInterface $serializer,
        ProductSubscriptionOptionFactory $productSubscriptionOptionFactory
    ) {
        $this->serializer = $serializer;
        $this->productSubscriptionOptionFactory = $productSubscriptionOptionFactory;
    }

    /**
     * @param ProductInterface $product
     * @return ProductSubscriptionOption[]
     */
    public function execute(ProductInterface $product): array
    {
        $table = $product->getData('mollie_subscription_table');
        if ($table === null) {
            return [];
        }

        $json = $this->serializer->unserialize($table);

        return array_map( function ($option) {
            return $this->productSubscriptionOptionFactory->create($option);
        }, $json);
    }
}
