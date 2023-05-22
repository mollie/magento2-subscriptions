<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterfaceFactory;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductSearchResultsInterface;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductSearchResultsInterfaceFactory;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Model\ResourceModel\SubscriptionToProduct as ResourceSubscriptionToProduct;
use Mollie\Subscriptions\Model\ResourceModel\SubscriptionToProduct\CollectionFactory as SubscriptionToProductCollectionFactory;

class SubscriptionToProductRepository implements SubscriptionToProductRepositoryInterface
{
    /**
     * @var ResourceSubscriptionToProduct
     */
    protected $resource;

    /**
     * @var SubscriptionToProductFactory
     */
    protected $subscriptionToProductFactory;

    /**
     * @var SubscriptionToProductCollectionFactory
     */
    protected $subscriptionToProductCollectionFactory;

    /**
     * @var SubscriptionToProductSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var SubscriptionToProductInterfaceFactory
     */
    protected $dataSubscriptionToProductFactory;

    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    public function __construct(
        ResourceSubscriptionToProduct $resource,
        SubscriptionToProductFactory $subscriptionToProductFactory,
        SubscriptionToProductInterfaceFactory $dataSubscriptionToProductFactory,
        SubscriptionToProductCollectionFactory $subscriptionToProductCollectionFactory,
        SubscriptionToProductSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->subscriptionToProductFactory = $subscriptionToProductFactory;
        $this->subscriptionToProductCollectionFactory = $subscriptionToProductCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataSubscriptionToProductFactory = $dataSubscriptionToProductFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface $subscriptionToProduct
    ) {
        $subscriptionToProductData = $this->extensibleDataObjectConverter->toNestedArray(
            $subscriptionToProduct,
            [],
            \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface::class
        );

        $subscriptionToProductModel = $this->subscriptionToProductFactory->create()->setData($subscriptionToProductData);

        try {
            $this->resource->save($subscriptionToProductModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the subscriptionToProduct: %1',
                $exception->getMessage()
            ));
        }
        return $subscriptionToProductModel->getDataModel();
    }

    public function productHasPriceUpdate(\Magento\Catalog\Api\Data\ProductInterface $product): bool
    {
        $this->resource->updateProductHasUpdateFor((int)$product->getId());

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get($subscriptionToProductId)
    {
        $subscriptionToProduct = $this->subscriptionToProductFactory->create();
        $this->resource->load($subscriptionToProduct, $subscriptionToProductId);
        if (!$subscriptionToProduct->getId()) {
            throw new NoSuchEntityException(__('subscription_to_product with id "%1" does not exist.', $subscriptionToProductId));
        }
        return $subscriptionToProduct->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getBySubscriptionId(string $subscriptionId) {
        $subscriptionToProduct = $this->subscriptionToProductFactory->create();
        $this->resource->load($subscriptionToProduct, $subscriptionId, 'subscription_id');
        if (!$subscriptionToProduct->getId()) {
            throw new NoSuchEntityException(__('subscription_to_product with id "%1" does not exist.', $subscriptionId));
        }
        return $subscriptionToProduct->getDataModel();
    }

    public function getSubscriptionsWithAPriceUpdate()
    {
        $collection = $this->subscriptionToProductCollectionFactory->create();
        $collection->addFieldToFilter('has_price_update', 1);
        $collection->setPageSize(100);

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface::class
        );

        $searchResults = $this->searchResultsFactory->create();

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->subscriptionToProductCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getByProductId(int $productId) {
        $collection = $this->subscriptionToProductCollectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface::class
        );

        $searchResults = $this->searchResultsFactory->create();

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getByCustomerIdAndProductId(string $mollieCustomerId, int $productId) {
        $collection = $this->subscriptionToProductCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $mollieCustomerId);
        $collection->addFieldToFilter('product_id', $productId);
        $collection->setPageSize(1);

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface::class
        );

        if (!$collection->count()) {
            throw new NotFoundException(__(
                'Subscription for customer %1 and product %2 not found',
                $mollieCustomerId,
                $productId
            ));
        }

        return $collection->getFirstItem();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface $subscriptionToProduct
    ) {
        try {
            $subscriptionToProductModel = $this->subscriptionToProductFactory->create();
            $this->resource->load($subscriptionToProductModel, $subscriptionToProduct->getEntityId());
            $this->resource->delete($subscriptionToProductModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the subscription_to_product: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteBySubscriptionId(string $customerId, string $subscriptionId): bool {
        try {
            $this->resource->deleteBySubscriptionId($customerId, $subscriptionId);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the subscription_to_product: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($entityId)
    {
        return $this->delete($this->get($entityId));
    }
}

