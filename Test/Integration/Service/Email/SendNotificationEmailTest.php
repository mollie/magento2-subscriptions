<?php

namespace Mollie\Subscriptions\Test\Integration\Service\Email;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Service\Email\SendNotificationEmail;
use Mollie\Subscriptions\Service\Email\SubscriptionToProductEmailVariables;

class SendNotificationEmailTest extends IntegrationTestCase
{
    public function sendNotificationEmailProvider(): array
    {
        return [
            ['adminNotification', 'admin', 'New subscription'],
            ['customerNotification', 'customer', 'Your new subscription'],
            ['adminRestartNotification', 'admin', 'Subscription restarted'],
            ['customerRestartNotification', 'customer', 'Subscription restarted'],
            ['adminCancelNotification', 'admin', 'Subscription canceled'],
            ['customerCancelNotification', 'customer', 'Subscription canceled'],
        ];
    }

    /**
     * @dataProvider sendNotificationEmailProvider
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_admin_notification 1
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_customer_notification 1
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_admin_restart_notification 1
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_customer_restart_notification 1
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_admin_cancel_notification 1
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_customer_cancel_notification 1
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @return void
     */
    public function testSendNotificationEmail(string $configSource, string $sendTo, string $expectedSubject): void
    {
        $customer = new \Mollie\Api\Resources\Customer(new MollieApiClient);
        $customer->email = 'john.doe@example.com';
        $customer->name = 'John Doe';

        $variablesMock = $this->createMock(SubscriptionToProductEmailVariables::class);
        $variablesMock->method('get')->willReturn([
            'subscription_description' => 'Test product - weekly',
            'subscription_nextPaymentDate' => '2016-11-19',
            'subscription_amount' => '999.99',
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
            'product' => $this->objectManager->create(ProductInterface::class),
        ]);
        $variablesMock->method('getMollieCustomer')->willReturn($customer);

        /** @var SendNotificationEmail $instance */
        $instance = $this->objectManager->create(SendNotificationEmail::class, [
            'configSource' => $configSource,
            'sendTo' => $sendTo,
            'emailVariables' => $variablesMock,
        ]);

        $subscriptionToProduct = $this->objectManager->create(SubscriptionToProductInterface::class);
        $subscriptionToProduct->setStoreId(1);

        $instance->execute($subscriptionToProduct);

        /** @var TransportBuilderMock $transportBuilder */
        $transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
        $subject = $transportBuilder->getSentMessage()->getSubject();

        $this->assertEquals($expectedSubject, $subject);
    }

    /**
     * @dataProvider sendNotificationEmailProvider
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_admin_notification 0
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_customer_notification 0
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_admin_restart_notification 0
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_customer_restart_notification 0
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_admin_cancel_notification 0
     * @magentoConfigFixture default_store mollie_subscriptions/emails/enable_customer_cancel_notification 0
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @return void
     */
    public function testWhenDisabledNoEmailIsSent(string $configSource, string $sendTo, string $expectedSubject): void
    {
        /** @var SendNotificationEmail $instance */
        $instance = $this->objectManager->create(SendNotificationEmail::class, [
            'configSource' => $configSource,
            'sendTo' => $sendTo,
        ]);

        $subscriptionToProduct = $this->objectManager->create(SubscriptionToProductInterface::class);
        $subscriptionToProduct->setStoreId(1);

        $instance->execute($subscriptionToProduct);

        /** @var TransportBuilderMock $transportBuilder */
        $transportBuilder = $this->objectManager->get(TransportBuilderMock::class);

        $this->assertNull($transportBuilder->getSentMessage());
    }
}
