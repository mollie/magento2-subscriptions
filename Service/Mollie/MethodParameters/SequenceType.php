<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie\MethodParameters;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Service\Mollie\Parameters\ParameterPartInterface;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class SequenceType implements ParameterPartInterface
{
    public function __construct(
        private readonly CartContainsSubscriptionProduct $cartContainsSubscriptionProduct
    ) {
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
