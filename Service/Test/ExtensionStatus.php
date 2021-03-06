<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Test;

use Mollie\Subscriptions\Config;

/**
 * Extension status test class
 */
class ExtensionStatus
{

    /**
     * Test type
     */
    const TYPE = 'extension_status';

    /**
     * Test description
     */
    const TEST = 'Check if the extension is enabled in the configuration';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'Extension is enabled';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'Extension disabled, please enable it!';

    /**
     * Expected result
     */
    const EXPECTED = true;

    /**
     * @var Config
     */
    private $config;

    /**
     * Repository constructor.
     *
     * @param Config $config
     */
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
            'visible' => self::VISIBLE
        ];

        if ($this->config->isEnabled() == self::EXPECTED) {
            $result['result_msg'] = self::SUCCESS_MSG;
            $result +=
                [
                    'result_code' => 'success',
                ];
        } else {
            $result['result_msg'] = self::FAILED_MSG;
            $result +=
                [
                    'result_code' => 'failed',
                ];
        }
        return $result;
    }
}
