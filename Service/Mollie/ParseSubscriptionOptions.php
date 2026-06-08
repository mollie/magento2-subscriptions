<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data;
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
    /**
     * @var Data
     */
    private $catalogHelper;

    public function __construct(
        SerializerInterface $serializer,
        ProductSubscriptionOptionFactory $productSubscriptionOptionFactory,
        Data $catalogHelper
    ) {
        $this->serializer = $serializer;
        $this->productSubscriptionOptionFactory = $productSubscriptionOptionFactory;
        $this->catalogHelper = $catalogHelper;
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

        return array_map(function ($option) use ($product) {
            if (array_key_exists('price', $option)) {
                $option['price'] = $this->addTaxToPrice($product, $option['price']);
            }

            return $this->productSubscriptionOptionFactory->create($option);
        }, $json);
    }

    private function addTaxToPrice(ProductInterface $product, float $price): float
    {
        return $this->catalogHelper->getTaxPrice(
            $product,
            $price,
            true,
            null,
            null,
            null,
            null,
            null,
            false
        );
    }
}
