<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Observer\MollieProcessTransactionEnd;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterfaceFactory;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\DTO\SubscriptionOption;
use Mollie\Subscriptions\Service\Email\SendNotificationEmail;
use Mollie\Subscriptions\Service\Mollie\SubscriptionOptions;
use Mollie\Subscriptions\Service\Order\OrderContainsSubscriptionProduct;

class CreateSubscriptions implements ObserverInterface
{
    /**
     * @var Mollie
     */
    private $mollieModel;

    /**
     * @var OrderContainsSubscriptionProduct
     */
    private $orderContainsSubscriptionProduct;

    /**
     * @var SubscriptionOptions
     */
    private $subscriptionOptions;

    /**
     * @var SubscriptionToProductInterfaceFactory
     */
    private $subscriptionToProductFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    /**
     * @var MollieApiClient|null
     */
    private $mollieApi;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var SendNotificationEmail
     */
    private $sendAdminNotificationEmail;

    /**
     * @var SendNotificationEmail
     */
    private $sendCustomerNotificationEmail;

    public function __construct(
        Config $config,
        Mollie $mollieModel,
        OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct,
        SubscriptionOptions $subscriptionOptions,
        SubscriptionToProductInterfaceFactory $subscriptionToProductFactory,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $eventManager,
        SendNotificationEmail $sendAdminNotificationEmail,
        SendNotificationEmail $sendCustomerNotificationEmail
    ) {
        $this->config = $config;
        $this->mollieModel = $mollieModel;
        $this->orderContainsSubscriptionProduct = $orderContainsSubscriptionProduct;
        $this->subscriptionOptions = $subscriptionOptions;
        $this->subscriptionToProductFactory = $subscriptionToProductFactory;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->eventManager = $eventManager;
        $this->orderRepository = $orderRepository;
        $this->sendAdminNotificationEmail = $sendAdminNotificationEmail;
        $this->sendCustomerNotificationEmail = $sendCustomerNotificationEmail;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');
        if ($order->getPayment()->getAdditionalInformation('subscription_created') ||
            !$this->orderContainsSubscriptionProduct->check($order)) {
            return;
        }

        $this->mollieApi = $this->mollieModel->getMollieApi($order->getStoreId());
        $payment = $this->getPayment($order);

        $subscriptions = $this->subscriptionOptions->forOrder($order);
        foreach ($subscriptions as $subscriptionOptions) {
            $this->createSubscription($payment->customerId, $subscriptionOptions);
        }

        $order->getPayment()->setAdditionalInformation('subscription_created', date('Y-m-d'));
        $this->orderRepository->save($order);
    }

    private function getPayment(OrderInterface $order)
    {
        $transactionId = $order->getPayment()->getAdditionalInformation()['mollie_id'];
        if (preg_match('/^ord_\w+$/', $transactionId)) {
            $order = $this->mollieApi->orders->get($transactionId, ['embed' => 'payments']);

            return $order->payments()->offsetGet(0);
        }

        return $this->mollieApi->payments->get($transactionId);
    }

    private function createSubscription(string $customerId, SubscriptionOption $subscriptionOptions)
    {
        $this->config->addToLog('request', ['customerId' => $customerId, 'options' => $subscriptionOptions->toArray()]);
        $subscription = $this->mollieApi->subscriptions->createForId($customerId, $subscriptionOptions->toArray());

        /** @var SubscriptionToProductInterface $model */
        $model = $this->subscriptionToProductFactory->create();
        $model->setCustomerId($subscription->customerId);
        $model->setSubscriptionId($subscription->id);
        $model->setProductId($subscriptionOptions->getProductId());
        $model->setStoreId($subscriptionOptions->getStoreId());
        $model->setNextPaymentDate($subscription->nextPaymentDate);

        $model = $this->subscriptionToProductRepository->save($model);

        $this->eventManager->dispatch('mollie_subscription_created', ['subscription' => $model]);

        $this->sendAdminNotificationEmail->execute($model);
        $this->sendCustomerNotificationEmail->execute($model);
    }
}
