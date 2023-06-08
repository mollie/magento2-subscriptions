<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Cron;

use Mollie\Payment\Config as MollieConfig;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Email\RetrieveRecordsForPrePaymentReminder;
use Mollie\Subscriptions\Service\Email\SendPrepaymentReminderEmail;
use Mollie\Subscriptions\Service\Mollie\CheckIfSubscriptionIsActive;

class SendPrePaymentReminderEmailCron
{
    /**
     * @var MollieConfig
     */
    private $mollieConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    /**
     * @var SendPrepaymentReminderEmail
     */
    private $sendPrepaymentReminderEmail;

    /**
     * @var CheckIfSubscriptionIsActive
     */
    private $checkIfSubscriptionIsActive;

    /**
     * @var RetrieveRecordsForPrePaymentReminder
     */
    private $retrieveRecordsForPrePaymentReminder;

    public function __construct(
        MollieConfig $mollieConfig,
        Config $config,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        SendPrepaymentReminderEmail $sendPrepaymentReminderEmail,
        CheckIfSubscriptionIsActive $checkIfSubscriptionIsActive,
        RetrieveRecordsForPrePaymentReminder $retrieveRecordsForPrePaymentReminder
    ) {
        $this->mollieConfig = $mollieConfig;
        $this->config = $config;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->sendPrepaymentReminderEmail = $sendPrepaymentReminderEmail;
        $this->checkIfSubscriptionIsActive = $checkIfSubscriptionIsActive;
        $this->retrieveRecordsForPrePaymentReminder = $retrieveRecordsForPrePaymentReminder;
    }

    public function execute()
    {
        $today = new \DateTimeImmutable();
        $subscriptions = $this->retrieveRecordsForPrePaymentReminder->execute($today);
        foreach ($subscriptions->getItems() as $subscription) {
            if (!$this->config->isPrepaymentReminderEnabled($subscription->getStoreId())) {
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
