<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Test\Integration\Model\Test;

use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Test\ExtensionStatus;

class ExtensionStatusTest extends IntegrationTestCase
{
    public function testReturnsErrorWhenTheModuleIsDisabled()
    {
        $configMock = $this->createMock(Config::class);
        $configMock->method('isEnabled')->willReturn(false);

        /** @var ExtensionStatus $instance */
        $instance = $this->objectManager->create(ExtensionStatus::class, [
            'config' => $configMock,
        ]);

        $result = $instance->execute();

        $this->assertEquals(ExtensionStatus::FAILED_MSG, $result['result_msg']);
    }

    public function testReturnsSuccessWhenTheModuleIsEnabled()
    {
        $configMock = $this->createMock(Config::class);
        $configMock->method('isEnabled')->willReturn(true);

        /** @var ExtensionStatus $instance */
        $instance = $this->objectManager->create(ExtensionStatus::class, [
            'config' => $configMock,
        ]);

        $result = $instance->execute();

        $this->assertEquals(ExtensionStatus::SUCCESS_MSG, $result['result_msg']);
    }
}
