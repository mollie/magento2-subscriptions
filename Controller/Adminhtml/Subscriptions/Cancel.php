<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Controller\Adminhtml\Subscriptions;

use Magento\Backend\App\Action;
use Magento\Framework\Event\ManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class Cancel extends Action
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Mollie
     */
    private $mollie;

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
        Mollie $mollie,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        ManagerInterface $eventManager
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->mollie = $mollie;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->eventManager = $eventManager;
    }

    public function execute()
    {
        $api = $this->mollie->getMollieApi($this->getRequest()->getParam('store_id'));
        $customerId = $this->getRequest()->getParam('customer_id');
        $subscriptionId = $this->getRequest()->getParam('subscription_id');

        try {
            $api->subscriptions->cancelForId($customerId, $subscriptionId);
            $model = $this->subscriptionToProductRepository->getBySubscriptionId($subscriptionId);
            $this->eventManager->dispatch('mollie_subscription_after_cancelled', ['model' => $model]);
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
