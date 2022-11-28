<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Test;

use Exception;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Api\Log\RepositoryInterface as LogRepository;

/**
 * Extension version version test class
 */
class ExtensionVersion
{

    /**
     * Test type
     */
    const TYPE = 'extension_version';

    /**
     * Test description
     */
    const TEST = 'Check if new extension version is available';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'Great, you are using the latest version.';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'Version %s is available, current version %s';

    /**
     * Expected result
     */
    const EXPECTED = '0';

    /**
     * Link to get support
     */
    const SUPPORT_LINK = 'https://www.magmodules.eu/help/magento2/update-extension.html';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var JsonSerializer
     */
    private $json;

    /**
     * @var File
     */
    private $file;

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * ExtensionVersion constructor.
     *
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param LogRepository $logRepository
     * @param JsonSerializer $json
     * @param File $file
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Config $config,
        LogRepository $logRepository,
        JsonSerializer $json,
        File $file
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->logRepository = $logRepository;
        $this->json = $json;
        $this->file = $file;
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
        $extensionVersion = $this->config->getExtensionVersion();
        try {
            $data = $this->file->fileGetContents(
                sprintf('http://version.magmodules.eu/%s.json', Config::EXTENSION_CODE)
            );
        } catch (Exception $e) {
            $this->logRepository->addDebugLog('Extension version test', $e->getMessage());
            $result['result_msg'] = self::SUCCESS_MSG;
            $result +=
                [
                    'result_code' => 'success',
                ];
            return $result;
        }
        $data = $this->json->unserialize($data);
        $versions = array_keys($data);
        $latest = reset($versions);

        if ($extensionVersion[0] == 'v') {
            $extensionVersion = substr($extensionVersion, 1);
        }

        if (version_compare($latest, $extensionVersion) == self::EXPECTED) {
            $result['result_msg'] = self::SUCCESS_MSG;
            $result +=
                [
                    'result_code' => 'success'
                ];
        } else {
            $result['result_msg'] = sprintf(
                self::FAILED_MSG,
                $latest,
                $extensionVersion
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
