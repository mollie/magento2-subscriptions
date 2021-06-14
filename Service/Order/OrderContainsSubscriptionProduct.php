<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class OrderContainsSubscriptionProduct
{
    /**
     * @var CartContainsSubscriptionProduct
     */
    private $cartContainsSubscriptionProduct;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    public function __construct(
        CartContainsSubscriptionProduct $cartContainsSubscriptionProduct,
        CartRepositoryInterface $cartRepository
    ) {
        $this->cartContainsSubscriptionProduct = $cartContainsSubscriptionProduct;
        $this->cartRepository = $cartRepository;
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
