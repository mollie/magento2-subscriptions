<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Test\Integration\Service\Mollie\MethodParameters;

use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;
use Mollie\Subscriptions\Service\Mollie\MethodParameters\SequenceType;
use Mollie\Subscriptions\Service\Order\OrderContainsSubscriptionProduct;

class SequenceTypeTest extends IntegrationTestCase
{
    public function testDoesNothingWhenTheCartDoesNotContainARecurringProduct()
    {
        $orderContainsSubscriptionProductMock = $this->createMock(OrderContainsSubscriptionProduct::class);
        $orderContainsSubscriptionProductMock->method('check')->willReturn(false);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'orderContainsSubscriptionProduct' => $orderContainsSubscriptionProductMock,
        ]);

        $result = $instance->enhance(
            ['empty' => true],
            $this->objectManager->create(CartInterface::class)
        );

        $this->assertEquals(['empty' => true], $result);
    }

    public function testIncludesTheSequenceTypeWhenTheCartContainsASubscriptionProduct()
    {
        $cartContainsSubscriptionProductMock = $this->createMock(CartContainsSubscriptionProduct::class);
        $cartContainsSubscriptionProductMock->method('check')->willReturn(true);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'cartContainsSubscriptionProduct' => $cartContainsSubscriptionProductMock,
        ]);

        $result = $instance->enhance(
            ['empty' => true],
            $this->objectManager->create(CartInterface::class)
        );

        $this->assertEquals(['empty' => true, 'sequenceType' => 'first'], $result);
    }
}
