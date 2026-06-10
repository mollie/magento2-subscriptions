<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Controller\Adminhtml\Log;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Mollie\Subscriptions\Config;

/**
 * Class debug
 *
 * AJAX controller to check debug log
 */
class Debug extends Action
{

    /**
     * Debug log file path pattern
     */
    const DEBUG_LOG_FILE = '%s/log/mollie-subscriptions/debug.log';

    public function __construct(
        Action\Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly DirectoryList $dir,
        private readonly File $file,
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
        if ($this->isLogExists(self::DEBUG_LOG_FILE)) {
            $result = ['result' => $this->prepareLogText(self::DEBUG_LOG_FILE)];
        } else {
            $result = __('Log is empty');
        }
        return $resultJson->setData($result);
    }

    /**
     * Check is log file exists
     *
     * @param string $file
     *
     * @return bool
     */
    private function isLogExists(string $file): bool
    {
        try {
            $logFile = sprintf($file, $this->dir->getPath('var'));
            return $this->file->isExists($logFile);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Prepare encoded log text
     *
     * @param string $file
     *
     * @return array
     * @throws FileSystemException
     */
    private function prepareLogText(string $file): array
    {
        $logFile = sprintf($file, $this->dir->getPath('var'));
        $fileContent = explode(PHP_EOL, $this->file->fileGetContents($logFile));
        if (count($fileContent) > 100) {
            $fileContent = array_slice($fileContent, -100, 100, true);
        }
        $result = [];
        foreach ($fileContent as $line) {
            $data = explode('] ', $line);
            $date = ltrim(array_shift($data), '[');
            $data = implode('] ', $data);
            $data = explode(': ', $data);
            array_shift($data);
            $result[] = [
                'date' => $date,
                'msg' => implode(': ', $data)
            ];
        }
        return $result;
    }
}
