<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Observer\CheckoutAllowGuest;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class ForceCustomerLoginWhenSubscriptionItemInCart implements ObserverInterface
{
    public function __construct(
        private readonly CartContainsSubscriptionProduct $cartContainsSubscriptionProduct
    ) {
    }

    public function execute(Observer $observer): void
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
