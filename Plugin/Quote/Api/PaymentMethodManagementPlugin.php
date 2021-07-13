<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Plugin\Quote\Api;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class PaymentMethodManagementPlugin
{
    const ALLOWED_METHODS = [
        'mollie_methods_bancontact',
        'mollie_methods_belfius',
        'mollie_methods_creditcard',
        'mollie_methods_eps',
        'mollie_methods_giropay',
        'mollie_methods_ideal',
        'mollie_methods_kbc',
        'mollie_methods_mybank',
        'mollie_methods_paypal',
        'mollie_methods_sofort',
    ];

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

    public function afterGetList(PaymentMethodManagementInterface $subject, $result, $cartId)
    {
        $cart = $this->cartRepository->get($cartId);

        if (!$this->cartContainsSubscriptionProduct->check($cart)) {
            return $result;
        }

        return array_filter($result, function ($method) {
            return in_array($method->getCode(), static::ALLOWED_METHODS);
        });
    }
}
