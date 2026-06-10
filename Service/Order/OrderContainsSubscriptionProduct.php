<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class OrderContainsSubscriptionProduct
{
    public function __construct(
        private readonly CartContainsSubscriptionProduct $cartContainsSubscriptionProduct,
        private readonly CartRepositoryInterface $cartRepository
    ) {
    }

    public function check(OrderInterface $order): bool
    {
        try {
            $quote = $this->cartRepository->get($order->getQuoteId());
        } catch (NoSuchEntityException $exception) {
            return false;
        }

        return $this->cartContainsSubscriptionProduct->check($quote);
    }
}
