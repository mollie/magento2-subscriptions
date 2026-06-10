<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Email;

use Assert\Assertion;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Config;

class SendNotificationEmail
{
    /**
     * @var string
     */
    private $enabledMethod;
    /**
     * @var string
     */
    private $templateMethod;

    public function __construct(
        private readonly Config $config,
        private readonly TransportBuilder $transportBuilder,
        private readonly IdentityInterface $identityContainer,
        private readonly SubscriptionToProductEmailVariables $emailVariables,
        private readonly SenderResolverInterface $senderResolver,
        private readonly LogEmail $logEmail,
        string $configSource,
        private readonly string $sendTo
    ) {
        $this->enabledMethod = 'enable' . ucfirst($configSource) . 'Email';
        $this->templateMethod = 'get' . ucfirst($configSource) . 'Template';

        Assertion::inArray($sendTo, ['admin', 'customer']);
    }

    public function execute(SubscriptionToProductInterface $subscriptionToProduct): void
    {
        $storeId = $subscriptionToProduct->getStoreId();
        if (!$this->config->{$this->enabledMethod}($storeId)) {
            return;
        }

        $templateId = $this->config->{$this->templateMethod}($storeId);
        $builder = $this->transportBuilder->setTemplateIdentifier($templateId);
        $builder->setTemplateOptions(['area' => 'frontend', 'store' => $storeId]);
        $emailIdentity = $this->identityContainer->getEmailIdentity();
        $builder->setFromByScope($emailIdentity, $storeId);
        $builder->setTemplateVars($this->emailVariables->get($subscriptionToProduct));

        $this->setTo($builder, $subscriptionToProduct);

        $transport = $builder->getTransport();
        $this->logEmail->execute($transport);
        $transport->sendMessage();
    }

    private function setTo(TransportBuilder $builder, SubscriptionToProductInterface $subscriptionToProduct): void
    {
        if ($this->sendTo == 'admin') {
            $emailIdentity = $this->identityContainer->getEmailIdentity();
            $info = $this->senderResolver->resolve($emailIdentity);
            $builder->addTo($info['email'], $info['name']);

            return;
        }

        $customer = $this->emailVariables->getMollieCustomer($subscriptionToProduct);
        $builder->addTo($customer->email, $customer->name);
    }
}
