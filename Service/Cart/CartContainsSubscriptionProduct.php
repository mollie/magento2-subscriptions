<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Cart;

use Magento\Quote\Api\Data\CartInterface;

class CartContainsSubscriptionProduct
{
    public function check(CartInterface $cart): bool
    {
        $items = $cart->getItemsCollection()->getItems();
        foreach ($items as $item) {
            if ($item->getProduct()->getData('mollie_subscription_product')) {
                return true;
            }
        }

        return false;
    }
}
