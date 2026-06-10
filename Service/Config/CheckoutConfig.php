<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Config;

use Magento\Checkout\Model\Session as CheckoutSession;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;
use Mollie\Subscriptions\Service\Cart\GetTrialDiscountForCart;

class CheckoutConfig implements \Magento\Checkout\Model\ConfigProviderInterface
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly CartContainsSubscriptionProduct $cartContainsSubscriptionProduct,
        private readonly GetTrialDiscountForCart $getTrialDiscountForCart
    ) {
    }


    public function getConfig()
    {
        $cart = $this->checkoutSession->getQuote();
        $trialDiscountForCart = $this->getTrialDiscountForCart->execute($cart);

        return [
            'mollie' => [
                'subscriptions' => [
                    'has_subscription_products_in_cart' => $this->cartContainsSubscriptionProduct->check($cart),
                    'has_trial_products_in_cart' => $trialDiscountForCart->getItemCount() != 0,
                    'trial' => [
                        'product' => $trialDiscountForCart->getProduct(),
                        'shipping' => $trialDiscountForCart->getShipping(),
                        'discount' => $trialDiscountForCart->getDiscount(),
                    ],
                ],
            ],
        ];
    }
}
