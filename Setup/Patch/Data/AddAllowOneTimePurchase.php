<?php

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Backend\Boolean;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\GiftMessage\Block\Adminhtml\Product\Helper\Form\Config;

class AddAllowOneTimePurchase implements DataPatchInterface
{
    public const ALLOW_ONE_TIME_PURCHASE = 'mollie_allow_one_time_purchase';

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply(): self
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();

        if (!$eavSetup->getAttribute(Product::ENTITY, static::ALLOW_ONE_TIME_PURCHASE)) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                static::ALLOW_ONE_TIME_PURCHASE,
                [
                    'group' => 'Mollie',
                    'backend' => Boolean::class,
                    'frontend' => '',
                    'label' => 'Allow One Time purchase for product',
                    'input' => 'select',
                    'class' => '',
                    'source' => Product\Attribute\Source\Boolean::class,
                    'global' => true,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => '',
                    'apply_to' => '',
                    'input_renderer' => Config::class,
                    'visible_on_front' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                ]
            );
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
}
