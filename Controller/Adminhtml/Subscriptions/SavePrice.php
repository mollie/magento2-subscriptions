<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Controller\Adminhtml\Subscriptions;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class SavePrice implements HttpPostActionInterface
{
    public function __construct(
        private readonly ManagerInterface $messageManager,
        private readonly RequestInterface $request,
        private readonly ResultFactory $resultFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly Config $config,
        private readonly MollieSubscriptionApi $mollieApi
    ) {
    }

    public function execute(): ResultInterface
    {
        $data = $this->request->getPostValue();
        $customerId = $data['customer_id'] ?? null;
        $subscriptionId = $data['subscription_id'] ?? null;
        $newPrice = $data['new_price'] ?? null;

        if (!$customerId || !$subscriptionId) {
            throw new LocalizedException(__('Customer ID and subscription ID are required.'));
        }

        try {
            $mollieClient = $this->mollieApi->loadByStore();

            $subscription = $mollieClient->subscriptions->getForId($customerId, $subscriptionId);

            $mollieClient->subscriptions->update($customerId, $subscriptionId, [
                'amount' => [
                    'value' => number_format((float)$newPrice, 2, '.', ''),
                    'currency' => $subscription->amount->currency,
                ]
            ]);

            $this->messageManager->addSuccessMessage(__('Subscription price has been updated successfully.'));
            $this->dataPersistor->clear('mollie_subscription_update_price');

            return $this->redirect('*/*/index');
        } catch (\Exception $exception) {
            $this->config->addToLog('Error updating subscription ' . $subscriptionId, $exception);
            $this->messageManager->addErrorMessage(__('Error updating subscription price: %1', $exception->getMessage()));
            $this->dataPersistor->set('mollie_subscription_update_price', $data);

            return $this->redirect('*/*/*', [
                'customer_id' => $customerId,
                'subscription_id' => $subscriptionId
            ]);
        }
    }

    private function redirect(string $to, array $arguments = []): ResultInterface
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setPath($to, $arguments);

        return $redirect;
    }
}
