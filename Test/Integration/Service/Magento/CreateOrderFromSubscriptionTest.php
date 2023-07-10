<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Test\Integration\Service\Magento;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Subscription;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;
use Mollie\Subscriptions\Service\Magento\CreateOrderFromSubscription;

class CreateOrderFromSubscriptionTest extends IntegrationTestCase
{
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

        $molliePaymentBuilder = $this->objectManager->get(MolliePaymentBuilder::class);
        $molliePaymentBuilder->setMethod('ideal');

        $payment = $molliePaymentBuilder->build();
        $payment->customerId = 'cst_testcustomer';

        $subscription = $this->objectManager->get(Subscription::class);
        $subscription->customerId = 'cst_testcustomer';
        $subscription->metadata = new \stdClass();
        $subscription->metadata->quantity = '1';
        $subscription->metadata->sku = 'simple';

        // If these aren't processed, the test will fail due to the customer not having a billing address
        $subscription->metadata->billingAddressId = $order->getBillingAddressId();
        $subscription->metadata->shippingAddressId = $order->getBillingAddressId();

        $instance = $this->objectManager->create(CreateOrderFromSubscription::class);

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
