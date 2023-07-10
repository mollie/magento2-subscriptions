<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Controller\Api;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\Order\LinkTransactionToOrder;
use Mollie\Payment\Service\Mollie\ValidateMetadata;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Service\Order\SendOrderEmails;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;
use Mollie\Subscriptions\Service\Magento\CreateOrderFromSubscription;
use Mollie\Subscriptions\Service\Mollie\RetryUsingOtherStoreViews;
use Mollie\Subscriptions\Service\Mollie\SendAdminNotification;

class Webhook extends Action implements CsrfAwareActionInterface
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
     * @var MollieSubscriptionApi
     */
    private $mollieSubscriptionApi;

    /**
     * @var MollieLogger
     */
    private $mollieLogger;

    /**
     * @var SendOrderEmails
     */
    private $sendOrderEmails;

    /**
     * @var RetryUsingOtherStoreViews
     */
    private $retryUsingOtherStoreViews;

    /**
     * @var MollieApiClient
     */
    private $api;

    /**
     * @var ValidateMetadata
     */
    private $validateMetadata;
    /**
     * @var SendAdminNotification
     */
    private $sendAdminNotification;

    /**
     * @var CreateOrderFromSubscription
     */
    private $createOrderFromSubscription;

    /**
     * @var LinkTransactionToOrder
     */
    private $linkTransactionToOrder;

    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    public function __construct(
        Context $context,
        Config $config,
        Mollie $mollie,
        MollieSubscriptionApi $mollieSubscriptionApi,
        MollieLogger $mollieLogger,
        SendOrderEmails $sendOrderEmails,
        RetryUsingOtherStoreViews $retryUsingOtherStoreViews,
        ValidateMetadata $validateMetadata,
        LinkTransactionToOrder $linkTransactionToOrder,
        OrderCommentHistory $orderCommentHistory,
        SendAdminNotification $sendAdminNotification,
        CreateOrderFromSubscription $createOrderFromSubscription
    ) {
        parent::__construct($context);

        $this->config = $config;
        $this->mollie = $mollie;
        $this->mollieSubscriptionApi = $mollieSubscriptionApi;
        $this->mollieLogger = $mollieLogger;
        $this->sendOrderEmails = $sendOrderEmails;
        $this->retryUsingOtherStoreViews = $retryUsingOtherStoreViews;
        $this->validateMetadata = $validateMetadata;
        $this->linkTransactionToOrder = $linkTransactionToOrder;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->sendAdminNotification = $sendAdminNotification;
        $this->createOrderFromSubscription = $createOrderFromSubscription;
    }

    public function execute()
    {
        if ($this->config->disableNewOrderConfirmation()) {
            $this->sendOrderEmails->disableOrderConfirmationSending();
        }

        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            throw new NotFoundException(__('No id provided'));
        }

        // The metadata is removed for recurring payments, which makes sense as we put the order ID in there,
        // so we need to skip the validation
        $this->validateMetadata->skipValidation();

        if ($orders = $this->mollie->getOrderIdsByTransactionId($id)) {
            foreach ($orders as $orderId) {
                $this->mollie->processTransaction($orderId, Payments::TRANSACTION_TYPE_SUBSCRIPTION);
            }

            return $this->returnOkResponse();
        }

        try {
            $molliePayment = $this->getPayment($id);
            $subscription = $this->api->subscriptions->getForId($molliePayment->customerId, $molliePayment->subscriptionId);

            $order = $this->createOrderFromSubscription->execute($this->api, $molliePayment, $subscription);

            $this->orderCommentHistory->add($order, __('Order created by Mollie subscription %1', $molliePayment->id));

            $this->linkTransactionToOrder->execute($molliePayment->id, $order);

            $this->mollie->processTransactionForOrder($order, Payments::TRANSACTION_TYPE_SUBSCRIPTION);

            return $this->returnOkResponse();
        } catch (\Throwable $exception) {
            $this->sendAdminNotification->send($id, $exception);

            $this->mollieLogger->addInfoLog('Error occurred while processing subscription', [
                'id' => $id,
                'exception' => $exception->__toString()
            ]);

            throw new NotFoundException(__('Please check the Mollie logs for more information'));
        }
    }

    private function returnOkResponse()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('content-type', 'text/plain');
        $result->setContents('OK');
        return $result;
    }

    public function getPayment(string $id): Payment
    {
        try {
            $this->api = $this->mollieSubscriptionApi->loadByStore();

            return $this->api->payments->get($id);
        } catch (ApiException $exception) {
            // If the store view is not set, try to get the payment using other store views
            if (!$this->getRequest()->getParam('___store')) {
                return $this->retryUsingOtherStoreViews->execute($id);
            }

            throw $exception;
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
