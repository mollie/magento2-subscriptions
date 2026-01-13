<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Test\Integration\Service\Magento;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Api\Http\Requests\GetSubscriptionRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;
use Mollie\Subscriptions\Service\Magento\CreateOrderFromSubscription;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;
use Mollie\Subscriptions\Test\Fakes\Service\Mollie\MollieSubscriptionApiFake;

class CreateOrderFromSubscriptionTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Customer/_files/customer_with_addresses.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testHandlesVirtualProductsCorrect(): void
    {
        $this->createMollieCustomer();
        $order = $this->loadOrderById('100000001');

        $subscription = [
            'id' => 'sub_testsubscription',
            'nextPaymentDate' => '2016-11-19',
            'metadata' => [
                'sku' => 'simple',
                // If these aren't processed, the test will fail due to the customer not having a billing address
                'billingAddressId' => $order->getBillingAddressId(),
            ]
        ];

        $client = MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok(json_encode([
                'id' => 'tr_testtransaction',
                'customerId' => 'cst_testcustomer',
                'subscriptionId' => 'sub_testsubscription',
            ])),

            GetSubscriptionRequest::class => MockResponse::ok(json_encode($subscription)),
        ]);

        /** @var MollieSubscriptionApiFake $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(MollieSubscriptionApiFake::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieSubscriptionApi::class);

        /** @var ProductInterface $product */
        $product = $this->objectManager->get(ProductRepositoryInterface::class)->get('simple');
        $product->setTypeId('virtual');

        $instance = $this->objectManager->create(CreateOrderFromSubscription::class);

        $payment = $fakeMollieApiClient->loadByStore()->payments->get('');
        $subscription = $fakeMollieApiClient->loadByStore()->subscriptions->getForId('', '');

        $order = $instance->execute(new MollieApiClient(), $payment, $subscription);

        $this->assertSame(1, $order->getIsVirtual());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer_with_addresses.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testUsesOrderBillingAndShippingAddresses(): void
    {
        $this->createMollieCustomer();
        $order = $this->loadOrderById('100000001');

        $subscription = [
            'id' => 'sub_testsubscription',
            'nextPaymentDate' => '2016-11-19',
            'metadata' => [
                'sku' => 'simple',
                // If these aren't processed, the test will fail due to the customer not having a billing address
                'billingAddressId' => $order->getBillingAddressId(),
                'shippingAddressId' => $order->getBillingAddressId(),
            ]
        ];

        $client = MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok(json_encode([
                'id' => 'tr_testtransaction',
                'customerId' => 'cst_testcustomer',
                'subscriptionId' => 'sub_testsubscription',
            ])),

            GetSubscriptionRequest::class => MockResponse::ok(json_encode($subscription)),
        ]);

        /** @var MollieSubscriptionApiFake $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(MollieSubscriptionApiFake::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieSubscriptionApi::class);

        $molliePaymentBuilder = $this->objectManager->get(MolliePaymentBuilder::class);
        $molliePaymentBuilder->setMethod('ideal');

        $instance = $this->objectManager->create(CreateOrderFromSubscription::class);

        $payment = $fakeMollieApiClient->loadByStore()->payments->get('');
        $subscription = $fakeMollieApiClient->loadByStore()->subscriptions->getForId('', '');

        $instance->execute(new MollieApiClient(), $payment, $subscription);

        $this->expectNotToPerformAssertions();
    }

    private function createMollieCustomer(): void
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $magentoCustomer = $customerRepository->get('customer_with_addresses@test.com');

        $customer = $this->objectManager->create(MollieCustomerInterface::class);
        $customer->setMollieCustomerId('cst_testcustomer');
        $customer->setCustomerId($magentoCustomer->getId());

        /** @var MollieCustomerRepositoryInterface $repository */
        $repository = $this->objectManager->get(MollieCustomerRepositoryInterface::class);
        $repository->save($customer);
    }
}
