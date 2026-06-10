<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data;
use Magento\Framework\Serialize\SerializerInterface;
use Mollie\Subscriptions\DTO\ProductSubscriptionOption;
use Mollie\Subscriptions\DTO\ProductSubscriptionOptionFactory;

class ParseSubscriptionOptions
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ProductSubscriptionOptionFactory $productSubscriptionOptionFactory,
        private readonly Data $catalogHelper
    ) {
    }

    /**
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
                $option['price'] = $this->addTaxToPrice($product, (float) $option['price']);
            }

            if (array_key_exists('trial_days', $option) && $option['trial_days'] !== null) {
                $option['trial_days'] = (int) $option['trial_days'];
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
