<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Magento;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Mollie\Api\Resources\Subscription;

class SubscriptionAddProductToCart
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var TaxCalculation
     */
    private $taxCalculation;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        TaxCalculation $taxCalculation
    ) {
        $this->productRepository = $productRepository;
        $this->taxCalculation = $taxCalculation;
    }

    public function execute(CartInterface $cart, Subscription $subscription): ProductInterface
    {
        $metadata = $subscription->metadata;
        $sku = $metadata->sku;
        $parentSku = isset($metadata->parent_sku) ? $metadata->parent_sku : null;
        $quantity = isset($metadata->quantity) ? (float)$metadata->quantity : 1;

        $product = $this->productRepository->get($parentSku ?: $sku);
        $cart->setIsVirtual($product->getIsVirtual());

        if (!$parentSku) {
            $item = $cart->addProduct($product, $quantity);
            $this->setSubscriptionPrice($cart, $item, $subscription);

            return $product;
        }

        $childProduct = $this->productRepository->get($sku);
        $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);

        $options = [];
        foreach ($productAttributeOptions as $option) {
            $options[$option['attribute_id']] = $childProduct->getData($option['attribute_code']);
        }

        $item = $cart->addProduct($product, new DataObject([
            'product' => $product->getId(),
            'qty' => $quantity,
            'super_attribute' => $options,
        ]));

        $this->setSubscriptionPrice($cart, $item, $subscription);

        return $product;
    }

    private function setSubscriptionPrice(CartInterface $cart, Item $item, Subscription $subscription): void
    {
        $request = $this->taxCalculation->getRateRequest(
            $cart->getShippingAddress(),
            $cart->getBillingAddress(),
            $cart->getCustomerTaxClassId(),
            $item->getStore()
        );
        $request->setProductClassId($item->getTaxClassId());
        $taxRate = $this->taxCalculation->getRate($request);

        $quantity = (float)($subscription->metadata->quantity ?? 1);
        $priceIncl = $subscription->amount->value / $quantity;
        $newPrice = $priceIncl;

        if ($taxRate !== 0.0) {
            $newPrice = $priceIncl / (1 + ($taxRate / 100));
        }

        $item->setCustomPrice($newPrice);
        $item->setOriginalCustomPrice($newPrice);
    }
}
