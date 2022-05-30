<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Block\Frontend\Product\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Model\Product\Attribute\Source\Boolean;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\DTO\ProductSubscriptionOption;
use Mollie\Subscriptions\Service\Mollie\ParseSubscriptionOptions;

class SubscriptionOptions extends Template
{
    protected $_template = 'Mollie_Subscriptions::product/view/subscription-options.phtml';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ParseSubscriptionOptions
     */
    private $parseSubscriptionOptions;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        Config $config,
        ParseSubscriptionOptions $parseSubscriptionOptions,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->config = $config;
        $this->parseSubscriptionOptions = $parseSubscriptionOptions;
    }

    /**
     * @return ProductSubscriptionOption[]
     */
    public function getOptions(): array
    {
        /** @var ProductInterface $product */
        $product = $this->registry->registry('current_product');

        return $this->parseSubscriptionOptions->execute($product);
    }

    public function allowOneTimePurchase(): bool
    {
        /** @var ProductInterface $product */
        $product = $this->registry->registry('current_product');

        $value = $product->getData('mollie_allow_one_time_purchase');

        if ($value == Boolean::VALUE_USE_CONFIG) {
            return $this->config->allowOneTimePurchase();
        }

        return (bool)$value;
    }
}
