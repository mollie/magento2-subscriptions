<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Controller\Index;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Mollie\Payment\Config;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Service\Email\SendNotificationEmail;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class Cancel extends Action implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly MollieSubscriptionApi $mollieSubscriptionApi,
        private readonly SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        private readonly CurrentCustomer $currentCustomer,
        private readonly Session $customerSession,
        private readonly ManagerInterface $eventManager,
        private readonly SendNotificationEmail $sendAdminCancelNotificationEmail,
        private readonly SendNotificationEmail $sendCustomerCancelNotificationEmail
    ) {
        parent::__construct($context);
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
        $canceled = false;

        $api = $this->mollieSubscriptionApi->loadByStore(storeId($customer->getStoreId()));
        $subscriptionId = $this->getRequest()->getParam('subscription_id');

        try {
            $model = $this->subscriptionToProductRepository->getBySubscriptionId($subscriptionId);
            $api->subscriptions->cancelForId($extensionAttributes->getMollieCustomerId(), $subscriptionId);
            $canceled = true;

            $this->sendAdminCancelNotificationEmail->execute($model);
            $this->sendCustomerCancelNotificationEmail->execute($model);
            $this->eventManager->dispatch('mollie_subscription_after_cancelled', ['subscription' => $model]);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Unable to cancel subscription'));
            $this->config->addToLog('error', [
                'message' => 'Unable to cancel subscription',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->_redirect('*/*/');
        } finally {
            if ($canceled) {
                $this->deleteSubscriptionReference($extensionAttributes->getMollieCustomerId(), $subscriptionId);
            }
        }

        $this->messageManager->addSuccessMessage(
            __('Subscription with ID "%1" has been cancelled', $subscriptionId)
        );

        return $this->_redirect('*/*/');
    }

    private function deleteSubscriptionReference(string $customerId, string $subscriptionId): void
    {
        $this->subscriptionToProductRepository->deleteBySubscriptionId($customerId, $subscriptionId);

        $this->eventManager->dispatch('mollie_subscription_cancelled', ['subscription_id' => $subscriptionId]);
    }
}
