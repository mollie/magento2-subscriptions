<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Test;

use Mollie\Subscriptions\Config;

/**
 * Magento version test class
 */
class MagentoVersion
{

    /**
     * Test type
     */
    const TYPE = 'magento_version';

    /**
     * Test description
     */
    const TEST = 'Check if current Magento version is supported for this module version';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'Magento version match';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'Minumum required Magento 2 version is %s, curent version is %s!';

    /**
     * Link to get support
     */
    const SUPPORT_LINK = 'https://www.magmodules.eu/help/magento2/minimum-magento-version.html';

    /**
     * Expected result
     */
    const EXPECTED = '2.3';

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function execute()
    {
        $result = [
            'type' => self::TYPE,
            'test' => self::TEST,
            'visible' => self::VISIBLE,

        ];
        $magentoVersion = $this->config->getMagentoVersion();
        if (version_compare(self::EXPECTED, $magentoVersion) <= 0) {
            $result['result_msg'] = self::SUCCESS_MSG;
            $result +=
                [
                    'result_code' => 'success'
                ];
        } else {
            $result['result_msg'] = sprintf(
                self::FAILED_MSG,
                self::EXPECTED,
                $magentoVersion
            );
            $result +=
                [
                    'result_code' => 'failed',
                    'support_link' => self::SUPPORT_LINK
                ];
        }
        return $result;
    }
}
