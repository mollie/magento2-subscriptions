<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Magento;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartInterface;

class SubscriptionAddProductToCart
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    public function execute(CartInterface $cart, object $metadata): ProductInterface
    {
        $sku = $metadata->sku;
        $parentSku = isset($metadata->parent_sku) ? $metadata->parent_sku : null;
        $quantity = isset($metadata->quantity) ? (float)$metadata->quantity : 1;

        $product = $this->productRepository->get($parentSku ?: $sku);
        $cart->setIsVirtual($product->getIsVirtual());

        if (!$parentSku) {
            $cart->addProduct($product, $quantity);

            return $product;
        }

        $childProduct = $this->productRepository->get($sku);
        $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);

        $options = [];
        foreach($productAttributeOptions as $option) {
            $options[$option['attribute_id']] =  $childProduct->getData($option['attribute_code']);
        }

        $cart->addProduct($product, new DataObject([
            'product' => $product->getId(),
            'qty' => $quantity,
            'super_attribute' => $options,
        ]));

        return $product;
    }
}
