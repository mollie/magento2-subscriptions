<?php

namespace Mollie\Subscriptions\Test\Integration\Controller\Api;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Encryption\Encryptor;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController as ControllerTestCase;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\Endpoints\SubscriptionEndpoint;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Subscription;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Test\Fakes\FakeEncryptor;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class WebhookTest extends ControllerTestCase
{
    public function testAcceptsPost()
    {
        $instance = $this->_objectManager->get(FakeEncryptor::class);
        $instance->addReturnValue('', 'test_dummyapikeythatisvalidandislongenough');

        $this->_objectManager->addSharedInstance($instance, Encryptor::class);

        $paymentEndpointMock = $this->createMock(PaymentEndpoint::class);
        $paymentEndpointMock->method('get')->willThrowException(new ApiException('Invalid transaction (Test)'));

        /** @var Mollie $mollie */
        $mollie = $this->_objectManager->get(Mollie::class);
        $api = $mollie->getMollieApi();
        $api->payments = $paymentEndpointMock;

        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->method('getMollieApi')->willReturn($api);
        $this->_objectManager->addSharedInstance($mollieMock, Mollie::class);

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setParams([
            'id' => 'ord_123ABC',
        ]);

        $this->dispatch('mollie-subscriptions/api/webhook');

        $this->assert404NotFound();
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer_with_addresses.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture default_store mollie_subscriptions/general/shipping_method flatrate_flatrate
     */
    public function testPlacesOrderFromTransaction(): void
    {
        $transactionId = 'tr_testtransaction';

        $this->createMollieCustomer();
        $api = $this->getApi($transactionId);

        $mollieSubscriptionApi = $this->createMock(MollieSubscriptionApi::class);
        $mollieSubscriptionApi->method('loadByStore')->willReturn($api);
        $this->_objectManager->addSharedInstance($mollieSubscriptionApi, MollieSubscriptionApi::class);

        // Check how many orders there are before the webhook is called
        $ordersCount = count($this->getOrderIdsByTransactionId($transactionId));

        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->method('processTransactionForOrder');
        $this->_objectManager->addSharedInstance($mollieMock, Mollie::class);

        $this->dispatch('mollie-subscriptions/api/webhook?id=' . $transactionId);
        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $orders = $this->getOrderIdsByTransactionId($transactionId);
        $this->assertSame($ordersCount + 1, count($orders));
    }

    private function createMollieCustomer(): void
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $magentoCustomer = $customerRepository->get('customer_with_addresses@test.com');
        $address = $magentoCustomer->getAddresses()[0];
        $address->setRegionId(1);

        // The fixture does not set the default billing and shipping address correct, so fix that.
        $magentoCustomer->setDefaultBilling($address->getId());
        $magentoCustomer->setDefaultShipping($address->getId());
        $customerRepository->save($magentoCustomer);

        // Save the Mollie customer
        /** @var MollieCustomerInterface $customer */
        $customer = $this->_objectManager->create(MollieCustomerInterface::class);
        $customer->setMollieCustomerId('cst_testcustomer');
        $customer->setCustomerId($magentoCustomer->getId());

        /** @var MollieCustomerRepositoryInterface $repository */
        $repository = $this->_objectManager->get(MollieCustomerRepositoryInterface::class);
        $repository->save($customer);
    }

    private function getOrderIdsByTransactionId(string $transactionId): array
    {
        /** @var OrderRepositoryInterface $repository */
        $repository = $this->_objectManager->get(OrderRepositoryInterface::class);

        /** @var SearchCriteriaBuilder $criteria */
        $criteria = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $criteria->addFilter('mollie_transaction_id', $transactionId);

        $list = $repository->getList($criteria->create());

        return $list->getItems();
    }

    public function getSubscription(): Subscription
    {
        /** @var Subscription $subscription */
        $subscription = $this->_objectManager->get(Subscription::class);
        $subscription->customerId = 'cst_testcustomer';
        $subscription->metadata = new \stdClass();
        $subscription->metadata->sku = 'simple';
        return $subscription;
    }

    public function getPayment(string $transactionId): Payment
    {
        $molliePaymentBuilder = $this->_objectManager->get(MolliePaymentBuilder::class);
        $molliePaymentBuilder->setMethod('ideal');
        $payment = $molliePaymentBuilder->build();

        $payment->id = $transactionId;
        $payment->customerId = 'cst_testcustomer';
        $payment->_links = new \stdClass();
        $payment->_links->subscription = new \stdClass();
        $payment->_links->subscription->href = 'https://example.com/mollie/subscriptions/sub_testsubscription';

        return $payment;
    }

    public function getApi(string $transactionId): MollieApiClient
    {
        $subscription = $this->getSubscription();

        $subscriptionsEndpointMock = $this->createMock(SubscriptionEndpoint::class);
        $subscriptionsEndpointMock->method('getForId')->willReturn($subscription);

        $payment = $this->getPayment($transactionId);

        $paymentEndpointMock = $this->createMock(PaymentEndpoint::class);
        $paymentEndpointMock->method('get')->willReturn($payment);

        /** @var Mollie $mollie */
        $api = $this->createMock(MollieApiClient::class);
        $api->method('performHttpCallToFullUrl')->willReturn($subscription);
        $api->payments = $paymentEndpointMock;
        $api->subscriptions = $subscriptionsEndpointMock;
        return $api;
    }
}
