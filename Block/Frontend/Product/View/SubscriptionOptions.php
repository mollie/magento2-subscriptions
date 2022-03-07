<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Block\Frontend\Product\View;

use Magento\Catalog\Block\Product\View;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
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
     * @var ParseSubscriptionOptions
     */
    private $parseSubscriptionOptions;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        ParseSubscriptionOptions $parseSubscriptionOptions,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->parseSubscriptionOptions = $parseSubscriptionOptions;
    }

    /**
     * @return ProductSubscriptionOption[]
     */
    public function getOptions(): array
    {
        $product = $this->registry->registry('current_product');

        return $this->parseSubscriptionOptions->execute($product);
    }
}
