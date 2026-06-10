<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Cron;

use Mollie\Payment\Config as MollieConfig;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Email\RetrieveRecordsForPrePaymentReminder;
use Mollie\Subscriptions\Service\Email\SendPrepaymentReminderEmail;
use Mollie\Subscriptions\Service\Mollie\CheckIfSubscriptionIsActive;

class SendPrePaymentReminderEmailCron
{
    public function __construct(
        private readonly MollieConfig $mollieConfig,
        private readonly Config $config,
        private readonly SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        private readonly SendPrepaymentReminderEmail $sendPrepaymentReminderEmail,
        private readonly CheckIfSubscriptionIsActive $checkIfSubscriptionIsActive,
        private readonly RetrieveRecordsForPrePaymentReminder $retrieveRecordsForPrePaymentReminder
    ) {
    }

    public function execute(): void
    {
        $today = new \DateTimeImmutable();
        $subscriptions = $this->retrieveRecordsForPrePaymentReminder->execute($today);
        foreach ($subscriptions->getItems() as $subscription) {
            if (!$this->config->isPrepaymentReminderEnabled(storeId($subscription->getStoreId()))) {
                continue;
            }

            if (!$this->checkIfSubscriptionIsActive->execute($subscription)) {
                continue;
            }

            $this->mollieConfig->addToLog(
                'info',
                sprintf(
                    'Sending prepayment reminder email for subscription "%s"',
                    $subscription->getEntityId()
                )
            );

            $this->sendPrepaymentReminderEmail->execute($subscription);

            $subscription->setLastReminderDate($today->format('Y-m-d'));
            $this->subscriptionToProductRepository->save($subscription);
        }
    }
}
