<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MakeAttributesInvisible implements DataPatchInterface
{
    public function __construct(
        private readonly Config $eavConfig,
        private readonly ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
    }

    public function apply(): self
    {
        foreach (['mollie_subscription_table', 'mollie_subscription_product'] as $attributeCode) {
            /** @var Attribute $attribute */
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);

            if ($attribute->getIsVisibleOnFront()) {
                $attribute->setData('is_visible_on_front', '0');
                $this->productAttributeRepository->save($attribute);
            }
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            SubscriptionAttributes::class,
            AddSubscriptionsTable::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
