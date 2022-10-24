<?php

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MakeAttributesInvisible implements DataPatchInterface
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    public function __construct(
        Config $eavConfig,
        ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->eavConfig = $eavConfig;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    public function apply()
    {
        foreach (['mollie_subscription_table', 'mollie_subscription_product'] as $attributeCode) {
            /** @var Attribute $attribute */
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);

            if ($attribute->getIsVisibleOnFront()) {
                $attribute->setData('is_visible_on_front', '0');
                $this->productAttributeRepository->save($attribute);
            }
        }
    }

    public static function getDependencies()
    {
        return [
            SubscriptionAttributes::class,
            AddSubscriptionsTable::class,
        ];
    }

    public function getAliases()
    {
        return [];
    }
}
