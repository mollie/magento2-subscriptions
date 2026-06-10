<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Observer\MollieProcessTransactionEnd;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Config;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterfaceFactory;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\DTO\SubscriptionOption;
use Mollie\Subscriptions\Service\Email\SendNotificationEmail;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;
use Mollie\Subscriptions\Service\Mollie\SubscriptionOptions;
use Mollie\Subscriptions\Service\Order\OrderContainsSubscriptionProduct;
use Throwable;

class CreateSubscriptions implements ObserverInterface
{
    private ?MollieApiClient $mollieApi = null;

    public function __construct(
        private readonly Config $config,
        private readonly MollieSubscriptionApi $mollieSubscriptionApi,
        private readonly OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct,
        private readonly SubscriptionOptions $subscriptionOptions,
        private readonly SubscriptionToProductInterfaceFactory $subscriptionToProductFactory,
        private readonly SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ManagerInterface $eventManager,
        private readonly SendNotificationEmail $sendAdminNotificationEmail,
        private readonly SendNotificationEmail $sendCustomerNotificationEmail
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        // Order not paid, so skipping.
        if (!in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE])) {
            return;
        }

        if ($order->getPayment()->getAdditionalInformation('subscription_created') ||
            !$this->orderContainsSubscriptionProduct->check($order)) {
            return;
        }

        $this->mollieApi = $this->mollieSubscriptionApi->loadByStore(storeId($order->getStoreId()));
        $payment = $this->getPayment($order);

        $subscriptions = $this->subscriptionOptions->forOrder($order);
        foreach ($subscriptions as $subscriptionOptions) {
            $this->createSubscription($payment->customerId, $subscriptionOptions);
        }

        $order->getPayment()->setAdditionalInformation('subscription_created', date('Y-m-d'));
        $this->orderRepository->save($order);
    }

    private function createSubscription(string $customerId, SubscriptionOption $subscriptionOptions): void
    {
        try {
            $this->config->addToLog('request', ['customerId' => $customerId, 'options' => $subscriptionOptions->toArray()]);
            $subscription = $this->mollieApi->subscriptions->createForId($customerId, $subscriptionOptions->toArray());

            /** @var SubscriptionToProductInterface $model */
            $model = $this->subscriptionToProductFactory->create();
            $model->setCustomerId($subscription->customerId);
            $model->setSubscriptionId($subscription->id);
            $model->setProductId($subscriptionOptions->getProductId());
            $model->setStoreId($subscriptionOptions->getStoreId());
            $model->setNextPaymentDate($subscription->nextPaymentDate);
            $model->setOptionId($subscriptionOptions->getOptionId());

            $model = $this->subscriptionToProductRepository->save($model);

            $this->eventManager->dispatch('mollie_subscription_created', ['subscription' => $model]);

            $this->sendAdminNotificationEmail->execute($model);
            $this->sendCustomerNotificationEmail->execute($model);
        } catch (Throwable $exception) {
            $this->config->addToLog('error', [
                'message' => 'Error while trying to create subscription for order',
                'subscription_options' => $subscriptionOptions->toArray(),
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    private function getPayment(OrderInterface $order)
    {
        $transactionId = $order->getPayment()->getAdditionalInformation()['mollie_id'];

        return $this->mollieApi->payments->get($transactionId);
    }
}
