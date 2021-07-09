<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Cron;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Email\SendPrepaymentReminderEmail;

class SendPrePaymentReminderEmailCron
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    /**
     * @var SendPrepaymentReminderEmail
     */
    private $sendPrepaymentReminderEmail;

    public function __construct(
        Config $config,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SendPrepaymentReminderEmail $sendPrepaymentReminderEmail
    ) {
        $this->config = $config;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->sendPrepaymentReminderEmail = $sendPrepaymentReminderEmail;
    }

    public function execute()
    {
        $interval = new \DateInterval('P' . $this->config->daysBeforePrepaymentReminder() . 'D');
        $prepaymentDate = (new \DateTimeImmutable)->sub($interval);

        $criteria = $this->searchCriteriaBuilderFactory->create();
        $criteria->addFilter('next_payment_date', $prepaymentDate->format('Y-m-d'), 'eq');

        $subscriptions = $this->subscriptionToProductRepository->getList($criteria->create());
        foreach ($subscriptions->getItems() as $subscription) {
            if (!$this->config->isPrepaymentReminderEnabled($subscription->getStoreId())) {
                continue;
            }

            $this->sendPrepaymentReminderEmail->execute($subscription);
        }
    }
}
