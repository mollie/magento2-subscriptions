<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddSubscriptionsTable implements DataPatchInterface
{
    public function __construct(
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply(): self
    {
        $eavSetup = $this->eavSetupFactory->create();

        if (!$eavSetup->getAttribute(Product::ENTITY, 'mollie_subscription_table')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'mollie_subscription_table',
                [
                    'group' => 'Mollie',
                    'label' => 'Subscription configuration',
                    'type' => 'text',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 60,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'visible' => true,
                    'visible_on_front' => false,
                    'frontend' => '',
                    'class' => '',
                    'user_defined' => false,
                    'default' => '',
                ]
            );
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
