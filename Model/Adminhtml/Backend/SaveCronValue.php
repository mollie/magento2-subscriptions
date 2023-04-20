<?php

namespace Mollie\Subscriptions\Model\Adminhtml\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class SaveCronValue extends Value
{
    const CRON_SCHEDULE_PATH = 'crontab/default/jobs/mollie_subscriptions_cron/schedule/cron_expr';

    /**
     * @var WriterInterface
     */
    private $configWriter;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        WriterInterface $configWriter,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->configWriter = $configWriter;
    }

    public function afterSave()
    {
        try {
            $this->configWriter->save(self::CRON_SCHEDULE_PATH, $this->getValue());
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__('Cron settings can\'t be saved'));
        }

        return parent::afterSave();
    }
}
