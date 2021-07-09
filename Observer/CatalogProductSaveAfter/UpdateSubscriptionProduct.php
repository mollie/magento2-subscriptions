<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mollie\Subscriptions\Observer\CatalogProductSaveAfter;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class UpdateSubscriptionProduct implements ObserverInterface
{
    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    public function __construct(
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository
    ) {
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getData('product');

        if (!$product->dataHasChangedFor('price')) {
            return;
        }

        $this->subscriptionToProductRepository->productHasPriceUpdate($product);
    }
}
