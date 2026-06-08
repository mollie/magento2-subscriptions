<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Email;

use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Subscription;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class SendForPayment
{
    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;
    /**
     * @var SendNotificationEmail
     */
    private $sendFailureNotificationEmail;

    public function __construct(
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        SendNotificationEmail $sendFailureNotificationEmail
    ) {
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->sendFailureNotificationEmail = $sendFailureNotificationEmail;
    }

    public function execute(Subscription $subscription, Payment $molliePayment): void
    {
        if ($molliePayment->status !== PaymentStatus::STATUS_FAILED) {
            return;
        }

        $subscriptionToProduct = $this->subscriptionToProductRepository->getBySubscriptionId($subscription->id);
        $this->sendFailureNotificationEmail->execute($subscriptionToProduct);
    }
}
