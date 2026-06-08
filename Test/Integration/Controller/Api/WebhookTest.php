<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Test\Integration\Controller\Api;

use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Encryption\Encryptor;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController as ControllerTestCase;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Api\Http\Requests\GetSubscriptionRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Magento\GetOrderIdsByTransactionId;
use Mollie\Payment\Test\Fakes\FakeEncryptor;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;
use Mollie\Subscriptions\Test\Fakes\Service\Mollie\MollieSubscriptionApiFake;

class WebhookTest extends ControllerTestCase
{
    /**
     * @return void
     */
    public function testAcceptsPost()
    {
        $instance = $this->_objectManager->get(FakeEncryptor::class);
        $instance->addReturnValue('', 'test_dummyapikeythatisvalidandislongenough');

        $this->_objectManager->addSharedInstance($instance, Encryptor::class);

        $this->mockSubscriptionApi();
        $this->mockGetOrderIdsByTransactionId();

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setParams([
            'id' => 'ord_123ABC',
        ]);

        $this->dispatch('mollie-subscriptions/api/webhook');

        $this->assert404NotFound();
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
        $this->_objectManager->addSharedInstance($mollieMock, Mollie::class);

        $this->mockGetOrderIdsByTransactionId([(int)$order->getEntityId()]);

        $this->dispatch('mollie-subscriptions/api/webhook?id=' . $transactionId);
        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $this->dispatch('mollie-subscriptions/api/webhook?id=' . $transactionId);
        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $this->assertEquals(
            0,
            // PHPUnit 9 and 10 compatibility fun
            method_exists($spy, 'getInvocationCount') ? $spy->getInvocationCount() : $spy->numberOfInvocations()
        );
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

        $this->mockSubscriptionApi();
        $this->mockGetOrderIdsByTransactionId();

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
     * @magentoDataFixture Magento/Customer/_files/customer_with_addresses.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoConfigFixture default_store mollie_subscriptions/general/shipping_method flatrate_flatrate
     */
    public function testPlacesOrderFromTransactionWithConfigurableProduct(): void
    {
        $transactionId = 'tr_testtransaction';

        $this->createMollieCustomer();

        $repository = $this->_objectManager->get(ProductRepository::class);
        $product = $repository->get('configurable');
        $childProducts = $product->getTypeInstance()->getUsedProducts($product);

        $this->mockSubscriptionApi($childProducts[0]->getSku(), 'configurable');
        $this->mockGetOrderIdsByTransactionId();

        // Check how many orders there are before the webhook is called
        $ordersCount = count($this->getOrderIdsByTransactionId($transactionId));

        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->method('processTransactionForOrder');
        $this->_objectManager->addSharedInstance($mollieMock, Mollie::class);

        $this->dispatch('mollie-subscriptions/api/webhook?id=' . $transactionId);
        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $orders = $this->getOrderIdsByTransactionId($transactionId);
        $this->assertSame($ordersCount + 1, count($orders));

        /** @var OrderInterface $lastOrder */
        $lastOrder = end($orders);

        $items = $lastOrder->getItems();
        $lastItem = end($items);

        $this->assertNotNull($lastItem->getParentItem());
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

        $this->mockSubscriptionApi();
        $this->mockGetOrderIdsByTransactionId();

        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->method('processTransactionForOrder');
        $this->_objectManager->addSharedInstance($mollieMock, Mollie::class);

        $this->dispatch('mollie-subscriptions/api/webhook?id=' . $transactionId);
        $this->assertEquals(200, $this->getResponse()->getStatusCode());

        $repository = $this->_objectManager->create(SubscriptionToProductRepositoryInterface::class);
        $subscription = $repository->getBySubscriptionId('sub_testsubscription');

        $this->assertEquals('2016-11-19', $subscription->getNextPaymentDate());
    }

    private function mockGetOrderIdsByTransactionId(array $orderIds = []): void
    {
        $mock = $this->createMock(GetOrderIdsByTransactionId::class);
        $mock->method('execute')->willReturn($orderIds);
        $this->_objectManager->addSharedInstance($mock, GetOrderIdsByTransactionId::class);
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

    private function loadOrderById($orderId): OrderInterface
    {
        $repository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $builder = $this->_objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteria = $builder->addFilter('increment_id', $orderId, 'eq')->create();

        $orderList = $repository->getList($searchCriteria)->getItems();

        return array_shift($orderList);
    }

    private function mockSubscriptionApi(string $sku = 'simple', ?string $parentSku = null): void
    {
        $subscription = [
            'id' => 'sub_testsubscription',
            'nextPaymentDate' => '2016-11-19',
            'amount' => ['value' => '100.00', 'currency' => 'EUR'],
            'metadata' => [
                'sku' => $sku,
            ]
        ];

        if ($parentSku) {
            $subscription['metadata']['parent_sku'] = $parentSku;
        }

        $client = MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok(json_encode([
                'id' => 'tr_testtransaction',
                'customerId' => 'cst_testcustomer',
                'subscriptionId' => 'sub_testsubscription',
            ])),

            GetSubscriptionRequest::class => MockResponse::ok(json_encode($subscription)),
        ]);

        /** @var MollieSubscriptionApiFake $fakeMollieApiClient */
        $fakeMollieApiClient = $this->_objectManager->get(MollieSubscriptionApiFake::class);
        $fakeMollieApiClient->setInstance($client);
        $this->_objectManager->addSharedInstance($fakeMollieApiClient, MollieSubscriptionApi::class);

        $this->createSubscriptionDatabaseRecord();
    }
}
