<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Plugin\Quote\Api;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class PaymentMethodManagementPlugin
{
    const ALLOWED_METHODS = [
        'mollie_methods_bancontact',
        'mollie_methods_belfius',
        'mollie_methods_creditcard',
        'mollie_methods_creditcard_vault',
        'mollie_methods_eps',
        'mollie_methods_ideal',
        'mollie_methods_kbc',
        'mollie_methods_mybank',
        'mollie_methods_paypal',
        'mollie_methods_sofort',
        'mollie_methods_trustly',
    ];

    public function __construct(
        private readonly CartContainsSubscriptionProduct $cartContainsSubscriptionProduct,
        private readonly CartRepositoryInterface $cartRepository
    ) {
    }

    public function afterGetList(PaymentMethodManagementInterface $subject, array $result, int $cartId): array
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
