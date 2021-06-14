<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Observer\CheckoutAllowGuest;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class ForceCustomerLoginWhenSubscriptionItemInCart implements ObserverInterface
{
    /**
     * @var CartContainsSubscriptionProduct
     */
    private $cartContainsSubscriptionProduct;

    public function __construct(
        CartContainsSubscriptionProduct $cartContainsSubscriptionProduct
    ) {
        $this->cartContainsSubscriptionProduct = $cartContainsSubscriptionProduct;
    }

    public function execute(Observer $observer)
    {
        /** @var CartInterface $cart */
        $cart = $observer->getData('quote');

        if ($this->cartContainsSubscriptionProduct->check($cart)) {
            /** @var DataObject $result */
            $result = $observer->getData('result');
            $result->setData('is_allowed', false);
        }
    }
}
