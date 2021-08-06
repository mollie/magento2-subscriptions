<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Test\Integration\Service\Mollie;

use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Config\Source\IntervalType;
use Mollie\Subscriptions\Config\Source\RepetitionType;
use Mollie\Subscriptions\DTO\SubscriptionOption;
use Mollie\Subscriptions\Service\Mollie\SubscriptionOptions;

class SubscriptionOptionsTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testHandlesInfiniteOptionCorrect()
    {
        $order = $this->loadOrder('100000001');
        $items = $order->getItems();
        $item = array_shift($items)->getProduct();

        $item->setData('mollie_subscription_product', 1);
        $item->setData('mollie_subscription_repetition_type', RepetitionType::INFINITE);

        /** @var SubscriptionOptions $instance */
        $instance = $this->objectManager->create(SubscriptionOptions::class);
        $result = $instance->forOrder($order);

        $this->assertCount(1, $result);
        $subscription = $result[0];
        $this->assertInstanceOf(SubscriptionOption::class, $subscription);
        $this->assertArrayNotHasKey('times', $subscription->toArray());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testIncludesTheTimesKey()
    {
        $order = $this->loadOrder('100000001');
        $items = $order->getItems();
        $item = array_shift($items)->getProduct();

        $item->setData('mollie_subscription_product', 1);
        $item->setData('mollie_subscription_repetition_amount', 10);
        $item->setData('mollie_subscription_repetition_type', RepetitionType::TIMES);

        /** @var SubscriptionOptions $instance */
        $instance = $this->objectManager->create(SubscriptionOptions::class);
        $result = $instance->forOrder($order);

        $this->assertCount(1, $result);
        $subscription = $result[0];
        $this->assertInstanceOf(SubscriptionOption::class, $subscription);
        $this->assertArrayHasKey('times', $subscription->toArray());
        $this->assertSame(10, $subscription->toArray()['times']);
    }

    /**
     * @dataProvider includesTheCorrectIntervalProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testIncludesTheCorrectInterval($input, $expected)
    {
        $order = $this->loadOrder('100000001');
        $items = $order->getItems();
        $item = array_shift($items)->getProduct();

        $item->setData('mollie_subscription_product', 1);
        $item->setData('mollie_subscription_interval_amount', $input['amount']);
        $item->setData('mollie_subscription_interval_type', $input['type']);

        /** @var SubscriptionOptions $instance */
        $instance = $this->objectManager->create(SubscriptionOptions::class);
        $result = $instance->forOrder($order);

        $this->assertCount(1, $result);
        $subscription = $result[0];
        $this->assertInstanceOf(SubscriptionOption::class, $subscription);
        $this->assertArrayHasKey('interval', $subscription->toArray());
        $this->assertSame($expected, $subscription->toArray()['interval']);
    }

    /**
     * @dataProvider addsADescriptionProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddsADescription($input, $expected)
    {
        $order = $this->loadOrder('100000001');
        $items = $order->getItems();
        $item = array_shift($items)->getProduct();

        $item->setData('mollie_subscription_product', 1);
        $item->setData('mollie_subscription_interval_amount', $input['amount']);
        $item->setData('mollie_subscription_interval_type', $input['type']);

        /** @var SubscriptionOptions $instance */
        $instance = $this->objectManager->create(SubscriptionOptions::class);
        $result = $instance->forOrder($order);

        $this->assertCount(1, $result);
        $subscription = $result[0];
        $this->assertInstanceOf(SubscriptionOption::class, $subscription);
        $this->assertArrayHasKey('description', $subscription->toArray());
        $this->assertStringContainsString($expected, $subscription->toArray()['description']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddsSku()
    {
        $order = $this->loadOrder('100000001');
        $items = $order->getItems();
        $item = array_shift($items)->getProduct();

        $item->setData('sku', 'example-sku');
        $item->setData('mollie_subscription_product', 1);
        $item->setData('mollie_subscription_interval_amount', 5);
        $item->setData('mollie_subscription_interval_type', IntervalType::MONTHS);

        /** @var SubscriptionOptions $instance */
        $instance = $this->objectManager->create(SubscriptionOptions::class);
        $result = $instance->forOrder($order);

        $this->assertCount(1, $result);
        $subscription = $result[0];
        $this->assertInstanceOf(SubscriptionOption::class, $subscription);
        $this->assertArrayHasKey('metadata', $subscription->toArray());
        $this->assertArrayHasKey('sku', $subscription->toArray()['metadata']);
        $this->assertEquals('example-sku', $subscription->toArray()['metadata']['sku']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddsTheAmount()
    {
        $order = $this->loadOrder('100000001');
        $items = $order->getItems();
        $item = array_shift($items);

//        $item->setRowTtotalInclTax(999.98);
        $item->setData('row_total_incl_tax', '999.98');
        $item->getProduct()->setData('mollie_subscription_product', 1);

        /** @var SubscriptionOptions $instance */
        $instance = $this->objectManager->create(SubscriptionOptions::class);
        $result = $instance->forOrder($order);

        $this->assertCount(1, $result);
        $subscription = $result[0];
        $this->assertInstanceOf(SubscriptionOption::class, $subscription);
        $this->assertArrayHasKey('amount', $subscription->toArray());
        $this->assertArrayHasKey('value', $subscription->toArray()['amount']);
        $this->assertEquals('999.98', $subscription->toArray()['amount']['value']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddsTheWebhookUrl()
    {
        $order = $this->loadOrder('100000001');
        $items = $order->getItems();
        $item = array_shift($items)->getProduct();

        $item->setData('mollie_subscription_product', 1);
        $item->setData('mollie_subscription_interval_amount', 5);
        $item->setData('mollie_subscription_interval_type', IntervalType::MONTHS);

        /** @var SubscriptionOptions $instance */
        $instance = $this->objectManager->create(SubscriptionOptions::class);
        $result = $instance->forOrder($order);

        $this->assertCount(1, $result);
        $subscription = $result[0];
        $this->assertInstanceOf(SubscriptionOption::class, $subscription);
        $this->assertArrayHasKey('webhookUrl', $subscription->toArray());
        $this->assertStringContainsString('api/webhook', $subscription->toArray()['webhookUrl']);
    }

    /**
     * @dataProvider addsTheStartDate
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @param $input
     * @param $expected
     */
    public function testAddsTheStartDate($input, $expected)
    {
        $order = $this->loadOrder('100000001');
        $items = $order->getItems();
        $item = array_shift($items)->getProduct();

        $item->setData('mollie_subscription_product', 1);
        $item->setData('mollie_subscription_interval_amount', $input['amount']);
        $item->setData('mollie_subscription_interval_type', $input['type']);

        /** @var SubscriptionOptions $instance */
        $instance = $this->objectManager->create(SubscriptionOptions::class);
        $result = $instance->forOrder($order);

        $this->assertCount(1, $result);
        $subscription = $result[0];
        $this->assertInstanceOf(SubscriptionOption::class, $subscription);
        $this->assertArrayHasKey('startDate', $subscription->toArray());
        $this->assertEquals($expected->format('Y-m-d'), $subscription->toArray()['startDate']);
    }

    public function includesTheCorrectIntervalProvider()
    {
        return [
            'day' => [['amount' => 7, 'type' => IntervalType::DAYS], '7 days'],
            'single week' => [['amount' => 1, 'type' => IntervalType::WEEKS], '1 weeks'],
            'multiple weeks' => [['amount' => 3, 'type' => IntervalType::WEEKS], '3 weeks'],
            'single month' => [['amount' => 1, 'type' => IntervalType::MONTHS], '1 months'],
            'multiple months' => [['amount' => 3, 'type' => IntervalType::MONTHS], '3 months'],
            'float months' => [['amount' => '3.0000', 'type' => IntervalType::MONTHS], '3 months'],
        ];
    }

    public function addsADescriptionProvider()
    {
        return [
            'single day' => [['amount' => 1, 'type' => IntervalType::DAYS], 'Every day'],
            'day' => [['amount' => 7, 'type' => IntervalType::DAYS], 'Every 7 days'],
            'single week' => [['amount' => 1, 'type' => IntervalType::WEEKS], 'Every week'],
            'multiple weeks' => [['amount' => 3, 'type' => IntervalType::WEEKS], 'Every 3 weeks'],
            'single month' => [['amount' => 1, 'type' => IntervalType::MONTHS], 'Every month'],
            'multiple months' => [['amount' => 3, 'type' => IntervalType::MONTHS], 'Every 3 months'],
            'float months' => [['amount' => '3.0000', 'type' => IntervalType::MONTHS], 'Every 3 months'],
        ];
    }

    public function addsTheStartDate()
    {
        $now = new \DateTimeImmutable('now');

        return [
            'single day' => [['amount' => 1, 'type' => IntervalType::DAYS], $now->add(new \DateInterval('P1D'))],
            'day' => [['amount' => 7, 'type' => IntervalType::DAYS], $now->add(new \DateInterval('P7D'))],
            'single week' => [['amount' => 1, 'type' => IntervalType::WEEKS], $now->add(new \DateInterval('P1W'))],
            'multiple weeks' => [['amount' => 3, 'type' => IntervalType::WEEKS], $now->add(new \DateInterval('P3W'))],
            'single month' => [['amount' => 1, 'type' => IntervalType::MONTHS], $now->add(new \DateInterval('P1M'))],
            'multiple months' => [['amount' => 3, 'type' => IntervalType::MONTHS], $now->add(new \DateInterval('P3M'))],
            'float months' => [['amount' => '3.0000', 'type' => IntervalType::MONTHS], $now->add(new \DateInterval('P3M'))],
        ];
    }
}
