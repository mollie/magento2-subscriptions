<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Service\Mollie\ParseSubscriptionOptions;

class AddOptionIdToMetadata implements DataPatchInterface
{
    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $repository;
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilder;
    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var ParseSubscriptionOptions
     */
    private $parseSubscriptionOptions;

    public function __construct(
        SubscriptionToProductRepositoryInterface $repository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilder,
        MollieApiClient $mollieApiClient,
        ProductRepositoryInterface $productRepository,
        ParseSubscriptionOptions $parseSubscriptionOptions
    ) {
        $this->repository = $repository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->mollieApiClient = $mollieApiClient;
        $this->productRepository = $productRepository;
        $this->parseSubscriptionOptions = $parseSubscriptionOptions;
    }

    public function apply(): self
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $result = $this->repository->getList($searchCriteria->create());
        foreach ($result->getItems() as $item) {
            try {
                $this->updateSubscription($item);
            } catch (\Exception $e) {}
        }

        return $this;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }

    private function updateSubscription(SubscriptionToProductInterface $item): void
    {
        $mollieApi = $this->mollieApiClient->loadByStore($item->getStoreId());
        $subscription = $mollieApi->subscriptions->getForId($item->getCustomerId(), $item->getSubscriptionId());

        $interval = $subscription->interval;
        $product = $this->productRepository->getById($item->getProductId());

        $options = $this->parseSubscriptionOptions->execute($product);

        foreach ($options as $option) {
            $optionInterval = (int)$option->getIntervalAmount() . ' ' . $option->getIntervalType();

            // We send X months, Mollie returns 1 month
            if ($optionInterval != $interval && $optionInterval != $interval . 's') {
                continue;
            }

            $item->setOptionId($option->getIdentifier());

            $this->repository->save($item);
            return;
        }
    }
}
