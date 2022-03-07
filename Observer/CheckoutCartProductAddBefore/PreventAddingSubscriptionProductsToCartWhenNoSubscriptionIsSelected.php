<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Observer\CheckoutCartProductAddBefore;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class PreventAddingSubscriptionProductsToCartWhenNoSubscriptionIsSelected implements ObserverInterface
{
    public function __construct()
    {
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getData('product');
        if (!$product->getData('mollie_subscription_product')) {
            return;
        }

        $info = $observer->getData('info');
        if (isset($info['purchase'], $info['recurring_metadata'], $info['recurring_metadata']['option_id'])) {
            return;
        }

        throw new LocalizedException(__('Please select a subscription before adding the product to your cart.'));
    }
}
