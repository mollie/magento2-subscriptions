<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Cart;

use Magento\Catalog\Model\Product\Attribute\Source\Boolean;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mollie\Subscriptions\Config;

class CartContainsSubscriptionProduct
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        Config $config,
        SerializerInterface $serializer
    ) {
        $this->config = $config;
        $this->serializer = $serializer;
    }

    public function check(CartInterface $cart): bool
    {
        /** @var CartItemInterface[] $items */
        $items = $cart->getItemsCollection()->getItems();
        foreach ($items as $item) {
            if ($this->itemIsSubscription($item)) {
                return true;
            }
        }

        return false;
    }

    private function itemIsSubscription(CartItemInterface $item): bool
    {
        if (!$item->getProduct()->getData('mollie_subscription_product')) {
            return false;
        }

        $allowOneTimePurchase = $item->getProduct()->getData('mollie_allow_one_time_purchase');

        // Disabled on product level, so the product is a subscription product
        if ($allowOneTimePurchase == Boolean::VALUE_NO ||
            (
                $allowOneTimePurchase == Boolean::VALUE_USE_CONFIG &&
                $this->config->allowOneTimePurchase($item->getStoreId()) == 0
            )
        ) {
            return true;
        }

        return !$this->oneTimePurchaseOptionIsSelected($item);
    }

    private function oneTimePurchaseOptionIsSelected(CartItemInterface $item): bool
    {
        $options = $item->getOptionsByCode();
        if (!isset($options['info_buyRequest'])) {
            return false;
        }

        $json = $options['info_buyRequest']->getData('value');
        $data = $this->serializer->unserialize($json);

        return isset($data['purchase']) && $data['purchase'] == 'onetime';
    }
}
