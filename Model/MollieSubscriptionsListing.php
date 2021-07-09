<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing;
use Mollie\Api\Resources\Subscription;
use Mollie\Api\Resources\SubscriptionCollection;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\DTO\SubscriptionResponse;

class MollieSubscriptionsListing extends Listing
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Mollie
     */
    private $mollieModel;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var MollieCustomerRepositoryInterface
     */
    private $mollieCustomerRepository;

    /**
     * @var string|null
     */
    private $next;

    /**
     * @var CustomerInterface[]
     */
    private $customers = [];

    /**
     * @var string|null
     */
    private $previous;

    public function __construct(
        ContextInterface $context,
        Config $config,
        Mollie $mollieModel,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CustomerInterfaceFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        MollieCustomerRepositoryInterface $mollieCustomerRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->mollieModel = $mollieModel;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->mollieCustomerRepository = $mollieCustomerRepository;
        $this->config = $config;
    }

    public function getDataSourceData()
    {
        $storeId = $this->getContext()->getRequestParam('filters')['store_id'] ?? null;
        $api = $this->mollieModel->getMollieApi($storeId);
        $paging = $this->getContext()->getRequestParam('paging');

        $result = $api->subscriptions->page(
            $this->getContext()->getRequestParam('offsetID'),
            $paging['pageSize'] ?? 20
        );

        $this->preloadCustomers((array)$result);
        $this->parsePreviousNext($result);

        $daysBeforeReminder = $this->config->daysBeforePrepaymentReminder($storeId);
        $items = array_map(function (Subscription $subscription) use ($daysBeforeReminder) {
            $prePaymentReminder = null;
            if ($subscription->nextPaymentDate) {
                $prePaymentReminder = new \DateTimeImmutable($subscription->nextPaymentDate);
                $prePaymentReminder = $prePaymentReminder->sub(new \DateInterval('P' . $daysBeforeReminder . 'D'));
            }

            $response = new SubscriptionResponse(
                $subscription,
                $this->getCustomerMollieCustomerById($subscription->customerId),
                $prePaymentReminder
            );

            return $response->toArray();
        }, (array)$result);

        return [
            'data' => [
                'items' => $items,
                'nextID' => $this->next,
                'previousID' => $this->previous,
            ],
        ];
    }

    private function preloadCustomers(array $result)
    {
        $mollieCustomerIds = array_column($result, 'customerId');

        $searchCriteria = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria->addFilter('mollie_customer_id', $mollieCustomerIds, 'in');
        $result = $this->mollieCustomerRepository->getList($searchCriteria->create());

        $customerIds = array_map(function (MollieCustomerInterface $customerInfo) {
            return $customerInfo->getCustomerId();
        }, $result->getItems());

        $searchCriteria = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria->addFilter('entity_id', $customerIds, 'in');
        $this->customers = $this->customerRepository->getList($searchCriteria->create())->getItems();
    }

    private function getCustomerMollieCustomerById(string $customerId)
    {
        foreach ($this->customers as $customer) {
            if ($customer->getExtensionAttributes()->getMollieCustomerId() == $customerId) {
                return $customer;
            }
        }

        return $this->customerFactory->create();
    }

    private function parsePreviousNext(SubscriptionCollection $result)
    {
        if ($result->hasNext()) {
            $this->next = $this->parseLink($result->_links->next->href);
        }

        if ($result->hasPrevious()) {
            $this->previous = $this->parseLink($result->_links->previous->href);
        }
    }

    private function parseLink(string $link): string
    {
        $query = parse_url($link, PHP_URL_QUERY);
        parse_str($query, $parts);

        return $parts['from'];
    }
}
