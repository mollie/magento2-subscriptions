<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Test\Integration\Service\Mollie;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Config\Source\IntervalType;
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

        /** @var OrderItemInterface $orderItem */
        $orderItem = array_shift($items);
        $this->setOptionIdOnOrderItem($orderItem, 'weekly-infinite');
        $this->setTheSubscriptionOnTheProduct($orderItem->getProduct());

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

        /** @var OrderItemInterface $orderItem */
        $orderItem = array_shift($items);
        $this->setOptionIdOnOrderItem($orderItem, 'weekly-finite');
        $this->setTheSubscriptionOnTheProduct($orderItem->getProduct());

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

        /** @var OrderItemInterface $orderItem */
        $orderItem = array_shift($items);
        $this->setOptionIdOnOrderItem($orderItem, 'custom');
        $this->setTheSubscriptionOnTheProduct(
            $orderItem->getProduct(),
            '{"identifier":"custom",' .
            '"title":"A new custom subscription",' .
            '"interval_amount":"' . $input['amount'] . '",' .
            '"interval_type":"' . $input['type'] . '",' .
            '"repetition_amount":"10",' .
            '"repetition_type":"times"' .
            '}'
        );

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

        /** @var OrderItemInterface $orderItem */
        $orderItem = array_shift($items);
        $this->setOptionIdOnOrderItem($orderItem, 'custom');
        $this->setTheSubscriptionOnTheProduct(
            $orderItem->getProduct(),
            '{"identifier":"custom",' .
            '"title":"A new custom subscription",' .
            '"interval_amount":"' . $input['amount'] . '",' .
            '"interval_type":"' . $input['type'] . '",' .
            '"repetition_amount":"10",' .
            '"repetition_type":"times"' .
            '}'
        );

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

        /** @var OrderItemInterface $orderItem */
        $orderItem = array_shift($items);

        $this->setOptionIdOnOrderItem($orderItem, 'weekly-finite');
        $this->setTheSubscriptionOnTheProduct($orderItem->getProduct());

        $orderItem->getProduct()->setData('sku', 'example-sku');

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

        /** @var OrderItemInterface $orderItem */
        $orderItem = array_shift($items);
        $orderItem->setData('row_total_incl_tax', '999.98');

        $this->setOptionIdOnOrderItem($orderItem, 'weekly-finite');
        $this->setTheSubscriptionOnTheProduct($orderItem->getProduct());

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

        /** @var OrderItemInterface $orderItem */
        $orderItem = array_shift($items);
        $this->setOptionIdOnOrderItem($orderItem, 'weekly-finite');
        $this->setTheSubscriptionOnTheProduct($orderItem->getProduct());

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

        /** @var OrderItemInterface $orderItem */
        $orderItem = array_shift($items);
        $this->setOptionIdOnOrderItem($orderItem, 'custom');
        $this->setTheSubscriptionOnTheProduct(
            $orderItem->getProduct(),
            '{"identifier":"custom",' .
            '"title":"A new custom subscription",' .
            '"interval_amount":"' . $input['amount'] . '",' .
            '"interval_type":"' . $input['type'] . '",' .
            '"repetition_amount":"10",' .
            '"repetition_type":"times"' .
            '}'
        );

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

    private function setTheSubscriptionOnTheProduct(ProductInterface $product, string $customSubscription = null): void
    {
        $product->setData('mollie_subscription_product', 1);

        $product->setData(
            'mollie_subscription_table',
            '[' .
            '{"identifier":"weekly-infinite","title":"A new product every week","interval_amount":"1","interval_type":"weeks","repetition_amount":"","repetition_type":"infinite"},' .
            '{"identifier":"bi-monthly-infinite","title":"A new product every other month","interval_amount":"2","interval_type":"months","repetition_type":"infinite"},' .
            '{"identifier":"weekly-finite","title":"A new product every week","interval_amount":"1","interval_type":"weeks","repetition_amount":"10","repetition_type":"times"}' .
            ($customSubscription ? ',' . $customSubscription : '') .
            ']'
        );
    }

    private function setOptionIdOnOrderItem(OrderItemInterface $orderItem, string $optionId): void
    {
        $orderItem->setData('product_options', [
            'info_buyRequest' => [
                'mollie_metadata' => [
                    'recurring_metadata' => [
                        'option_id' => $optionId,
                    ]
                ]
            ]
        ]);
    }
}
