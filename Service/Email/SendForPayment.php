<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Email;

use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Subscription;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class SendForPayment
{
    public function __construct(
        private readonly SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        private readonly SendNotificationEmail $sendFailureNotificationEmail
    ) {
    }

    public function execute(Subscription $subscription, Payment $molliePayment): void
    {
        if ($molliePayment->status !== PaymentStatus::FAILED) {
            return;
        }

        $subscriptionToProduct = $this->subscriptionToProductRepository->getBySubscriptionId($subscription->id);
        $this->sendFailureNotificationEmail->execute($subscriptionToProduct);
    }
}
