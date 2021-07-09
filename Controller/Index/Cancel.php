<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Controller\Index;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class Cancel extends Action implements HttpPostActionInterface
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
     * @var CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    public function __construct(
        Context $context,
        Config $config,
        Mollie $mollie,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        CurrentCustomer $currentCustomer,
        Session $customerSession,
        ManagerInterface $eventManager
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->mollie = $mollie;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->currentCustomer = $currentCustomer;
        $this->customerSession = $customerSession;
        $this->eventManager = $eventManager;
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

        $api = $this->mollie->getMollieApi();
        $subscriptionId = $this->getRequest()->getParam('subscription_id');

        try {
            $api->subscriptions->cancelForId($extensionAttributes->getMollieCustomerId(), $subscriptionId);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Unable to cancel subscription'));
            $this->config->addToLog('error', [
                'message' => 'Unable to cancel subscription',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->_redirect('*/*/');
        } finally {
            $this->deleteSubscriptionReference($extensionAttributes->getMollieCustomerId(), $subscriptionId);
        }

        $this->messageManager->addSuccessMessage(
            __('Subscription with ID "%1" has been cancelled', $subscriptionId)
        );

        return $this->_redirect('*/*/');
    }

    private function deleteSubscriptionReference(string $customerId, string $subscriptionId)
    {
        $this->subscriptionToProductRepository->deleteBySubscriptionId($customerId, $subscriptionId);

        $this->eventManager->dispatch('mollie_subscription_cancelled', ['subscription_id' => $subscriptionId]);
    }
}
