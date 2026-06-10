<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Controller\Adminhtml\Subscriptions;

use Magento\Backend\App\Action;
use Magento\Framework\Event\ManagerInterface;
use Mollie\Payment\Config;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class Cancel extends Action
{
    public function __construct(
        Action\Context $context,
        private readonly Config $config,
        private readonly MollieSubscriptionApi $mollieSubscriptionApi,
        private readonly SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        private readonly ManagerInterface $eventManager
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $api = $this->mollieSubscriptionApi->loadByStore(storeId($this->getRequest()->getParam('store_id')));
        $customerId = $this->getRequest()->getParam('customer_id');
        $subscriptionId = $this->getRequest()->getParam('subscription_id');
        $canceled = false;

        try {
            $api->subscriptions->cancelForId($customerId, $subscriptionId);
            $canceled = true;
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
            if ($canceled) {
                $this->subscriptionToProductRepository->deleteBySubscriptionId($customerId, $subscriptionId);
                $this->eventManager->dispatch('mollie_subscription_cancelled', ['subscription_id' => $subscriptionId]);
            }
        }

        $this->messageManager->addSuccessMessage(
            __('Subscription with ID "%1" has been cancelled', $subscriptionId)
        );

        return $this->_redirect('*/*/');
    }
}
