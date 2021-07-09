<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Mollie\MethodParameters;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Service\Mollie\Parameters\ParameterPartInterface;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class SequenceType implements ParameterPartInterface
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

    public function enhance(array $parameters, CartInterface $cart): array
    {
        if (!$this->cartContainsSubscriptionProduct->check($cart)) {
            return $parameters;
        }

        $parameters['sequenceType'] = 'first';

        return $parameters;
    }
}
