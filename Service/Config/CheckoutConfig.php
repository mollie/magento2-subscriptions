<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Config;

use Magento\Checkout\Model\Session as CheckoutSession;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class CheckoutConfig implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CartContainsSubscriptionProduct
     */
    private $cartContainsSubscriptionProduct;

    public function __construct(
        CheckoutSession $checkoutSession,
        CartContainsSubscriptionProduct $cartContainsSubscriptionProduct
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartContainsSubscriptionProduct = $cartContainsSubscriptionProduct;
    }


    public function getConfig()
    {
        $cart = $this->checkoutSession->getQuote();

        return [
            'mollie' => [
                'subscriptions' => [
                    'has_subscription_products_in_cart' => $this->cartContainsSubscriptionProduct->check($cart),
                ],
            ],
        ];
    }
}
