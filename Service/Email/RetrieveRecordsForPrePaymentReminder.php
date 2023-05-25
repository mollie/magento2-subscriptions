<?php

namespace Mollie\Subscriptions\Service\Email;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductSearchResultsInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Config;

class RetrieveRecordsForPrePaymentReminder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

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
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->config = $config;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
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
