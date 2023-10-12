<?php

namespace Mollie\Subscriptions\Test\Integration\Controller\Api;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Encryption\Encryptor;
use Magento\Sales\Api\Data\OrderInterface;
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
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class WebhookTest extends ControllerTestCase
{
    /**
     * @var Subscription
     */
    private $subscription;

    public function testAcceptsPost()
    {
        $instance = $this->_objectManager->get(FakeEncryptor::class);
        $instance->addReturnValue('', 'test_dummyapikeythatisvalidandislongenough');

        $this->_objectManager->addSharedInstance($instance, Encryptor::class);

        $paymentEndpointMock = $this->createMock(PaymentEndpoint::class);
        $paymentEndpointMock->method('get')->willThrowException(new ApiException('Invalid transaction (Test)'));

        /** @var Mollie $mollie */
        $api = new MollieApiClient();
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

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Customer/_files/customer_with_addresses.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture default_store mollie_subscriptions/general/shipping_method flatrate_flatrate
     */
    public function testDoesNotCreateMultipleOrders(): void
    {
        $transactionId = 'tr_testtransaction';

        $order = $this->loadOrderById('100000001');

        $mollieSubscriptionApiMock = $this->createMock(MollieSubscriptionApi::class);
        $mollieSubscriptionApiMock->expects($spy = $this->any())->method('loadByStore');

        $this->_objectManager->addSharedInstance($mollieSubscriptionApiMock, MollieSubscriptionApi::class);

        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->method('processTransactionForOrder');
        $mollieMock->method('getOrderIdsByTransactionId')->willReturn([$order]);
        $this->_objectManager->addSharedInstance($mollieMock, Mollie::class);

        $this->dispatch('mollie-subscriptions/api/webhook?id=' . $transactionId);
        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $this->dispatch('mollie-subscriptions/api/webhook?id=' . $transactionId);
        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $this->assertEquals(0, $spy->getInvocationCount());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer_with_addresses.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture default_store mollie_subscriptions/general/shipping_method flatrate_flatrate
     */
    public function testUpdatesNextPaymentDate(): void
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

        $repository = $this->_objectManager->create(SubscriptionToProductRepositoryInterface::class);
        $subscription = $repository->getBySubscriptionId('sub_testsubscription');

        $this->assertEquals('2019-11-19', $subscription->getNextPaymentDate());
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

    private function getSubscription(): Subscription
    {
        /** @var Subscription $subscription */
        $subscription = $this->_objectManager->get(Subscription::class);
        $subscription->id = 'sub_testsubscription';
        $subscription->customerId = 'cst_testcustomer';
        $subscription->metadata = new \stdClass();
        $subscription->metadata->sku = 'simple';
        $subscription->nextPaymentDate = '2019-11-19';
        return $subscription;
    }

    private function getPayment(string $transactionId): Payment
    {
        $molliePaymentBuilder = $this->_objectManager->get(MolliePaymentBuilder::class);
        $molliePaymentBuilder->setMethod('ideal');
        $payment = $molliePaymentBuilder->build();

        $payment->id = $transactionId;
        $payment->customerId = 'cst_testcustomer';
        $payment->subscriptionId = 'sub_testsubscription';
        $payment->_links = new \stdClass();
        $payment->_links->subscription = new \stdClass();
        $payment->_links->subscription->href = 'https://example.com/mollie/subscriptions/sub_testsubscription';

        return $payment;
    }

    private function getApi(string $transactionId): MollieApiClient
    {
        $subscription = $this->getSubscription();
        $this->subscription = $subscription;

        $this->createSubscriptionDatabaseRecord();

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

    private function loadOrderById($orderId): OrderInterface
    {
        $repository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $builder = $this->_objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteria = $builder->addFilter('increment_id', $orderId, 'eq')->create();

        $orderList = $repository->getList($searchCriteria)->getItems();

        return array_shift($orderList);
    }

    private function createSubscriptionDatabaseRecord(): void
    {
        /** @var SubscriptionToProductInterface $subscription */
        $subscription = $this->_objectManager->create(SubscriptionToProductInterface::class);
        $subscription->setSubscriptionId('sub_testsubscription');
        $subscription->setNextPaymentDate('2019-11-12');
        $subscription->setCustomerId('cst_testcustomer');
        $subscription->setProductId(1);

        $this->_objectManager->get(SubscriptionToProductRepositoryInterface::class)->save($subscription);
    }
}
