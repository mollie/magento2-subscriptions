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
 * Class Changelog
 *
 * AJAX controller to check latest extension version changelog
 */
class Changelog extends Action
{
    public function __construct(
        Action\Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly JsonSerializer $json,
        private readonly File $file
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws FileSystemException
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = $this->getVersions();
        $data = $this->json->unserialize($result);
        return $resultJson->setData($data);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    private function getVersions(): string
    {
        return $this->file->fileGetContents(
            sprintf('http://version.magmodules.eu/%s.json', Config::EXTENSION_CODE)
        );
    }
}
