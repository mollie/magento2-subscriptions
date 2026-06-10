<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Email;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductSearchResultsInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Config;

class RetrieveRecordsForPrePaymentReminder
{
    public function __construct(
        private readonly Config $config,
        private readonly SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        private readonly SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        private readonly FilterBuilder $filterBuilder,
        private readonly FilterGroupBuilder $filterGroupBuilder
    ) {
    }

    public function execute(\DateTimeImmutable $today): SubscriptionToProductSearchResultsInterface
    {
        $interval = new \DateInterval('P' . $this->config->daysBeforePrepaymentReminder() . 'D');
        $prepaymentDate = $today->add($interval);

        $criteria = $this->searchCriteriaBuilderFactory->create();

        $nextPaymentDate = $this->filterBuilder
            ->setField('next_payment_date')
            ->setConditionType('eq')
            ->setValue($prepaymentDate->format('Y-m-d'))
            ->create();

        $lastReminderDateNull = $this->filterBuilder
            ->setField('last_reminder_date')
            ->setConditionType('null')
            ->create();

        $lastReminderDateNotToday = $this->filterBuilder
            ->setField('last_reminder_date')
            ->setConditionType('neq')
            ->setValue($today->format('Y-m-d'))
            ->create();

        $criteria->setFilterGroups([
            $this->filterGroupBuilder
                ->addFilter($nextPaymentDate)
                ->create(),
            $this->filterGroupBuilder
                ->addFilter($lastReminderDateNull)
                ->addFilter($lastReminderDateNotToday)
                ->create()
        ]);

        return $this->subscriptionToProductRepository->getList($criteria->create());
    }
}
