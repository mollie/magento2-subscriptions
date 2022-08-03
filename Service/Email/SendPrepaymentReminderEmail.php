<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Email;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Config;

class SendPrepaymentReminderEmail
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var IdentityInterface
     */
    private $identityContainer;

    /**
     * @var SubscriptionToProductEmailVariables
     */
    private $emailVariables;

    public function __construct(
        Config $config,
        TransportBuilder $transportBuilder,
        IdentityInterface $identityContainer,
        SubscriptionToProductEmailVariables $emailVariables
    ) {
        $this->config = $config;
        $this->transportBuilder = $transportBuilder;
        $this->identityContainer = $identityContainer;
        $this->emailVariables = $emailVariables;
    }

    public function execute(SubscriptionToProductInterface $subscriptionToProduct)
    {
        $storeId = $subscriptionToProduct->getStoreId();
        $templateId = $this->config->prepaymentReminderTemplate($storeId);
        $builder = $this->transportBuilder->setTemplateIdentifier($templateId);
        $builder->setTemplateOptions(['area' => 'frontend', 'store' => $storeId]);
        $emailIdentity = $this->identityContainer->getEmailIdentity();
        $builder->setFromByScope($emailIdentity, $storeId);
        $builder->setTemplateVars($this->emailVariables->get($subscriptionToProduct));

        $customer = $this->emailVariables->getMollieCustomer($subscriptionToProduct);
        $builder->addTo($customer->email, $customer->name);

        $transport = $builder->getTransport();
        $transport->sendMessage();
    }
}
