<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Controller\Index;

use Magento\Catalog\Model\Product;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Api\Resources\Subscription;
use Mollie\Payment\Config;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterfaceFactory;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Service\Email\SendNotificationEmail;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class Restart extends Action implements HttpPostActionInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var MollieSubscriptionApi
     */
    private $mollieSubscriptionApi;

    /**
     * @var CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var SubscriptionToProductInterfaceFactory
     */
    private $subscriptionToProductFactory;

    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var SendNotificationEmail
     */
    private $sendAdminRestartNotificationEmail;

    /**
     * @var SendNotificationEmail
     */
    private $sendCustomerRestartNotificationEmail;

    public function __construct(
        Context $context,
        Config $config,
        MollieSubscriptionApi $mollieSubscriptionApi,
        CurrentCustomer $currentCustomer,
        Session $customerSession,
        SubscriptionToProductInterfaceFactory $subscriptionToProductFactory,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        StoreManagerInterface $storeManager,
        ManagerInterface $eventManager,
        Product $product,
        SendNotificationEmail $sendAdminRestartNotificationEmail,
        SendNotificationEmail $sendCustomerRestartNotificationEmail
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->mollieSubscriptionApi = $mollieSubscriptionApi;
        $this->currentCustomer = $currentCustomer;
        $this->customerSession = $customerSession;
        $this->subscriptionToProductFactory = $subscriptionToProductFactory;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
        $this->product = $product;
        $this->sendAdminRestartNotificationEmail = $sendAdminRestartNotificationEmail;
        $this->sendCustomerRestartNotificationEmail = $sendCustomerRestartNotificationEmail;
    }

    public function dispatch(RequestInterface $request)
    {
        if (!$this->customerSession->authenticate()) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    public function execute()
    {
        $customer = $this->currentCustomer->getCustomer();
        $extensionAttributes = $customer->getExtensionAttributes();

        $api = $this->mollieSubscriptionApi->loadByStore($customer->getStoreId());
        $subscriptionId = $this->getRequest()->getParam('subscription_id');

        $canceledSubscription = $api->subscriptions->getForId(
            $extensionAttributes->getMollieCustomerId(),
            $subscriptionId
        );

        try {
            $subscription = $api->subscriptions->createForId($extensionAttributes->getMollieCustomerId(), [
                'amount' => [
                    'currency' => $canceledSubscription->amount->currency,
                    'value' => $canceledSubscription->amount->value,
                ],
                'times' => $canceledSubscription->times,
                'interval' => $canceledSubscription->interval,
                'description' => $canceledSubscription->description,
                'metadata' => $this->getMetadata($canceledSubscription),
                'webhookUrl' => $this->_url->getUrl(
                    'mollie-subscriptions/api/webhook',
                    ['___store' => $this->storeManager->getStore()->getCode()],
                ),
            ]);

            $this->saveSubscriptionResult($subscription);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('We are unable to restart the subscription'));

            $this->config->addToLog('error', [
                'message' => 'Unable to restart the subscription with ID ' . $canceledSubscription->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->_redirect('*/*/index');
        }

        $this->messageManager->addSuccessMessage('The subscription has been restarted successfully');

        return $this->_redirect('*/*/index');
    }

    private function getMetadata(Subscription $canceledSubscription)
    {
        if ($canceledSubscription->metadata instanceof \stdClass) {
            $metadata = $canceledSubscription->metadata;
            $metadata->parent_id = $canceledSubscription->id;

            return $metadata;
        }

        return [];
    }

    private function saveSubscriptionResult(Subscription $subscription)
    {
        $productId = $this->product->getIdBySku($subscription->metadata->sku);

        /** @var SubscriptionToProductInterface $model */
        $model = $this->subscriptionToProductFactory->create();
        $model->setCustomerId($subscription->customerId);
        $model->setSubscriptionId($subscription->id);
        $model->setProductId($productId);
        $model->setStoreId($this->storeManager->getStore()->getId());
        $model->setNextPaymentDate($subscription->nextPaymentDate);

        $model = $this->subscriptionToProductRepository->save($model);
        $this->eventManager->dispatch('mollie_subscription_restarted', ['subscription' => $model]);

        $this->sendAdminRestartNotificationEmail->execute($model);
        $this->sendCustomerRestartNotificationEmail->execute($model);
    }
}
