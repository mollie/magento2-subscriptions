<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Email;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Config;

class SendPrepaymentReminderEmail
{
    public function __construct(
        private readonly Config $config,
        private readonly TransportBuilder $transportBuilder,
        private readonly IdentityInterface $identityContainer,
        private readonly SubscriptionToProductEmailVariables $emailVariables,
        private readonly LogEmail $logEmail
    ) {
    }

    public function execute(SubscriptionToProductInterface $subscriptionToProduct): void
    {
        $storeId = storeId($subscriptionToProduct->getStoreId());
        $templateId = $this->config->prepaymentReminderTemplate($storeId);
        $builder = $this->transportBuilder->setTemplateIdentifier($templateId);
        $builder->setTemplateOptions(['area' => 'frontend', 'store' => $storeId]);
        $emailIdentity = $this->identityContainer->getEmailIdentity();
        $builder->setFromByScope($emailIdentity, $storeId);
        $builder->setTemplateVars($this->emailVariables->get($subscriptionToProduct));

        $customer = $this->emailVariables->getMollieCustomer($subscriptionToProduct);
        $builder->addTo($customer->email, $customer->name);

        if ($bcc = $this->config->prepaymentSendBccTo($storeId)) {
            $builder->addBcc(explode(',', $bcc));
        }

        $transport = $builder->getTransport();
        $this->logEmail->execute($transport);
        $transport->sendMessage();
    }
}
