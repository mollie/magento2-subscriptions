<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Block\Adminhtml\Magmodules;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Mollie\Subscriptions\Config;

/**
 * System Configration Module information Block
 */
class Header extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Mollie_Subscriptions::system/config/fieldset/header.phtml';

    /**
     * @var Config
     */
    private $config;

    /**
     * Header constructor.
     *
     * @param Context $context
     * @param Config $config
     */
    public function __construct(
        Context $context,
        Config $config
    ) {
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->addClass('magmodules');

        return $this->toHtml();
    }

    /**
     * Image with extension and magento version.
     *
     * @return string
     */
    public function getImage(): string
    {
        return sprintf(
            'https://www.magmodules.eu/logo/%s/%s/%s/logo.png',
            $this->config->getExtensionCode(),
            $this->config->getExtensionVersion(),
            $this->config->getMagentoVersion()
        );
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink(): string
    {
        return $this->config->getSupportLink();
    }
}
