<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Controller\Adminhtml\VersionCheck;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Mollie\Subscriptions\Config;

/**
 * Class index
 *
 * AJAX controller to check latest extension version
 */
class Index extends Action
{

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
     * Check constructor.
     *
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param JsonSerializer $json
     * @param File $file
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        Config $config,
        JsonSerializer $json,
        File $file
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->json = $json;
        $this->file = $file;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = $this->getVersions();
        $current = $latest = $this->config->getExtensionVersion();
        $changeLog = [];
        if ($result) {
            $data = $this->json->unserialize($result);
            $versions = array_keys($data);
            $latest = reset($versions);
            foreach ($data as $version => $changes) {
                if ('v' . $version == $this->config->getExtensionVersion()) {
                    break;
                }
                $changeLog[] = [
                    $version => $changes['changelog']
                ];
            }
        }
        $data = [
            'current_verion' => $current,
            'last_version' => $latest,
            'changelog' => $changeLog,
        ];
        return $resultJson->setData(['result' => $data]);
    }

    /**
     * @return string
     */
    private function getVersions(): string
    {
        try {
            return $this->file->fileGetContents(
                sprintf('http://version.magmodules.eu/%s.json', Config::EXTENSION_CODE)
            );
        } catch (\Exception $exception) {
            return '';
        }
    }
}
