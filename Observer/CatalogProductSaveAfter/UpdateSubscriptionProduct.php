<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mollie\Subscriptions\Observer\CatalogProductSaveAfter;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class UpdateSubscriptionProduct implements ObserverInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    public function __construct(
        SerializerInterface $serializer,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository
    ) {
        $this->serializer = $serializer;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getData('product');

        if ($product->dataHasChangedFor('price')) {
            $this->subscriptionToProductRepository->productHasPriceUpdate($product);
        }

        if ($product->dataHasChangedFor('mollie_subscription_table') &&
            $this->subscriptionTableHasPriceUpdate($product)
        ) {
            $this->subscriptionToProductRepository->productHasPriceUpdate($product);
        }
    }

    private function subscriptionTableHasPriceUpdate(ProductInterface $product): bool
    {
        $oldRaw = $product->getOrigData('mollie_subscription_table');
        $newRaw = $product->getData('mollie_subscription_table');

        $old = $this->safeUnserializeToArray($oldRaw);
        $new = $this->safeUnserializeToArray($newRaw);

        $oldMapping = $this->getIdentifierToPriceMapping($old);
        $newMapping = $this->getIdentifierToPriceMapping($new);

        foreach ($oldMapping as $identifier => $price) {
            if (array_key_exists($identifier, $newMapping) && $newMapping[$identifier] != $price) {
                return true;
            }
        }

        return false;
    }

    /**
     * Safely convert a serialized value or array into a normalized array.
     * - Null/empty/false values are treated as empty arrays
     * - Already-array values are returned as-is
     * - Any unserialize errors result in an empty array
     *
     * @param mixed $value
     */
    private function safeUnserializeToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '' || $value === false) {
            return [];
        }

        try {
            $result = $this->serializer->unserialize($value);
            return is_array($result) ? $result : [];
        } catch (\InvalidArgumentException $e) {
            return [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getIdentifierToPriceMapping(array $table): array
    {
        $output = [];
        foreach ($table as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!isset($row['identifier']) || $row['identifier'] === '' || $row['identifier'] === null) {
                continue;
            }
            $price = $row['price'] ?? null; // Price is optional
            $output[$row['identifier']] = $price;
        }

        return $output;
    }
}
