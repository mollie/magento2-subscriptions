<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

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
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ResultFactory
     */
    private $resultFactory;
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var MollieSubscriptionApi
     */
    private $mollieApi;

    public function __construct(
        ManagerInterface $messageManager,
        RequestInterface $request,
        ResultFactory $resultFactory,
        DataPersistorInterface $dataPersistor,
        Config $config,
        MollieSubscriptionApi $mollieApi
    ) {
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->dataPersistor = $dataPersistor;
        $this->config = $config;
        $this->mollieApi = $mollieApi;
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
