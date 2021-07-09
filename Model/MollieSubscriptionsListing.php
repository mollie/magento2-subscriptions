<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing;
use Mollie\Api\Resources\Subscription;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\DTO\SubscriptionResponse;

class MollieSubscriptionsListing extends Listing
{
    /**
     * @var Mollie
     */
    private $mollieModel;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var MollieCustomerRepositoryInterface
     */
    private $mollieCustomerRepository;

    /**
     * @var CustomerInterface[]
     */
    private $customers = [];

    /**
     * @var string|null
     */
    private $next;

    /**
     * @var string|null
     */
    private $previous;

    public function __construct(
        ContextInterface $context,
        Mollie $mollieModel,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CustomerRepositoryInterface $customerRepository,
        MollieCustomerRepositoryInterface $mollieCustomerRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->mollieModel = $mollieModel;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->customerRepository = $customerRepository;
        $this->mollieCustomerRepository = $mollieCustomerRepository;
    }

    public function getDataSourceData()
    {
        $api = $this->mollieModel->getMollieApi($this->getContext()->getRequestParam('filters')['store_id'] ?? null);
        $paging = $this->getContext()->getRequestParam('paging');

        $result = $api->subscriptions->page(
            $this->getContext()->getRequestParam('offsetID'),
            $paging['pageSize'] ?? 20
        );

        $this->preloadCustomers((array)$result);
        $this->parsePreviousNext($result);

        $items = array_map(function (Subscription $subscription) {
            $response = new SubscriptionResponse(
                $subscription,
                $this->getCustomerMollieCustomerById($subscription->customerId)
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

        return null;
    }

    private function parsePreviousNext(\Mollie\Api\Resources\SubscriptionCollection $result)
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
