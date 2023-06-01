<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Controller\Adminhtml\Subscriptions;

use Magento\Backend\App\Action;
use Magento\Framework\Event\ManagerInterface;
use Mollie\Payment\Config;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class Cancel extends Action
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
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    public function __construct(
        Action\Context $context,
        Config $config,
        MollieSubscriptionApi $mollieSubscriptionApi,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        ManagerInterface $eventManager
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->mollieSubscriptionApi = $mollieSubscriptionApi;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->eventManager = $eventManager;
    }

    public function execute()
    {
        $api = $this->mollieSubscriptionApi->loadByStore($this->getRequest()->getParam('store_id'));
        $customerId = $this->getRequest()->getParam('customer_id');
        $subscriptionId = $this->getRequest()->getParam('subscription_id');

        try {
            $api->subscriptions->cancelForId($customerId, $subscriptionId);
            $model = $this->subscriptionToProductRepository->getBySubscriptionId($subscriptionId);
            $this->eventManager->dispatch('mollie_subscription_after_cancelled', ['subscription' => $model]);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Unable to cancel subscription: %1', $exception->getMessage()));

            $this->config->addToLog('error', [
                'message' => 'Unable to cancel subscription',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->_redirect('*/*/');
        } finally {
            $this->subscriptionToProductRepository->deleteBySubscriptionId($customerId, $subscriptionId);
            $this->eventManager->dispatch('mollie_subscription_cancelled', ['subscription_id' => $subscriptionId]);
        }

        $this->messageManager->addSuccessMessage(
            __('Subscription with ID "%1" has been cancelled', $subscriptionId)
        );

        return $this->_redirect('*/*/');
    }
}
