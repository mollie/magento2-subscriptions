<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Magento;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Service\Mollie\ParseSubscriptionOptions;

class GetPriceUpdateForSubscription
{
    /**
     * @var ParseSubscriptionOptions
     */
    private $parseSubscriptionOptions;
    /**
     * @var Data
     */
    private $catalogData;

    public function __construct(
        ParseSubscriptionOptions $parseSubscriptionOptions,
        Data $catalogData
    )
    {
        $this->parseSubscriptionOptions = $parseSubscriptionOptions;
        $this->catalogData = $catalogData;
    }

    /**
     * The issue: A subscription can have the price of a product, but it can also have a price that belongs to the
     * chosen subscription. Example: Product price = 200, monthly price = 30. When the price is updated we need to
     * calculate the new price for a specific subscription.
     */
    public function execute(SubscriptionToProductInterface $item, ProductInterface $product): float
    {
        $price = $this->getPrice($product, $item);

        return $this->catalogData->getTaxPrice(
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

    public function getPrice(ProductInterface $product, SubscriptionToProductInterface $item): float
    {
        $options = $this->parseSubscriptionOptions->execute($product);
        foreach ($options as $option) {
            if ($option->getIdentifier() !== $item->getOptionId()) {
                continue;
            }

            if ($option->getPrice() === null) {
                return $product->getPrice();
            }

            return $option->getPrice();
        }

        return $product->getPrice();
    }
}
