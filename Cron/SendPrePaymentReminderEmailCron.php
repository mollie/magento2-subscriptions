<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Cron;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
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

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    public function __construct(
        Config $config,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SendPrepaymentReminderEmail $sendPrepaymentReminderEmail,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->config = $config;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->sendPrepaymentReminderEmail = $sendPrepaymentReminderEmail;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
    }

    public function execute()
    {
        $today = (new \DateTime())->format('Y-m-d');

        $interval = new \DateInterval('P' . $this->config->daysBeforePrepaymentReminder() . 'D');
        $prepaymentDate = (new \DateTimeImmutable())->sub($interval);

        $criteria = $this->searchCriteriaBuilderFactory->create();
        $criteria->addFilter('next_payment_date', $prepaymentDate->format('Y-m-d'), 'eq');

        $lastReminderDateNull = $this->filterBuilder
            ->setField('last_reminder_date')
            ->setConditionType('null')
            ->create();

        $lastReminderDateNotToday = $this->filterBuilder
            ->setField('last_reminder_date')
            ->setConditionType('neq')
            ->setValue($today)
            ->create();

        $criteria->setFilterGroups([
            $this->filterGroupBuilder
                ->addFilter($lastReminderDateNull)
                ->addFilter($lastReminderDateNotToday)
                ->create()
        ]);

        $subscriptions = $this->subscriptionToProductRepository->getList($criteria->create());
        foreach ($subscriptions->getItems() as $subscription) {
            if (!$this->config->isPrepaymentReminderEnabled($subscription->getStoreId())) {
                continue;
            }

            $this->sendPrepaymentReminderEmail->execute($subscription);

            $subscription->setLastReminderDate($today);
            $this->subscriptionToProductRepository->save($subscription);
        }
    }
}
