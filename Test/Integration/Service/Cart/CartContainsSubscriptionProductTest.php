<?php

namespace Mollie\Subscriptions\Test\Integration\Service\Cart;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class CartContainsSubscriptionProductTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @return void
     */
    public function testAllowOneTimePurchaseIsDisabled(): void
    {
        /** @var Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        /** @var CartContainsSubscriptionProduct $instance */
        $instance = $this->objectManager->create(CartContainsSubscriptionProduct::class);
        $result = $instance->check($quote);

        $this->assertFalse($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @return void
     */
    public function testReturnsTrueWhenOneTimePurchaseIsNotSet(): void
    {
        /** @var Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        $items = $quote->getAllItems();
        array_shift($items)->getProduct()->setData('mollie_subscription_product', 1);

        /** @var CartContainsSubscriptionProduct $instance */
        $instance = $this->objectManager->create(CartContainsSubscriptionProduct::class);
        $result = $instance->check($quote);

        $this->assertTrue($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @return void
     */
    public function testAllowsOneTimePurchaseButIsNotOneTimePurchase(): void
    {
        /** @var Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        $items = $quote->getAllItems();
        $product = array_shift($items)->getProduct();
        $product->setData('mollie_subscription_product', 1);
        $product->setData('mollie_allow_one_time_purchase', 1);

        /** @var CartContainsSubscriptionProduct $instance */
        $instance = $this->objectManager->create(CartContainsSubscriptionProduct::class);
        $result = $instance->check($quote);

        $this->assertTrue($result);
    }
}
