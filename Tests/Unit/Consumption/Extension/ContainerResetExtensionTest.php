<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearerInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerResetExtension;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks\ChainExtensionAwareClearer;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ContainerResetExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ClearerInterface|\PHPUnit\Framework\MockObject\MockObject $clearer1;

    private ClearerInterface|\PHPUnit\Framework\MockObject\MockObject $clearer2;

    private ChainExtensionAwareClearer|\PHPUnit\Framework\MockObject\MockObject $chainExtensionAwareClearer;

    private ContainerResetExtension $extension;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    protected function setUp(): void
    {
        $this->clearer1 = $this->getMockBuilder(ClearerInterface::class)
            ->addMethods(['setChainExtension'])
            ->getMockForAbstractClass();
        $this->clearer2 = $this->getMockBuilder(ClearerInterface::class)
            ->addMethods(['setChainExtension'])
            ->getMockForAbstractClass();
        $this->chainExtensionAwareClearer = $this->createMock(ChainExtensionAwareClearer::class);

        $this->extension = new ContainerResetExtension(
            [
                $this->clearer1,
                $this->clearer2,
                $this->chainExtensionAwareClearer,
            ]
        );

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testOnPostReceivedForPersistentAndNonPersistentProcessors(): void
    {
        $this->extension->setPersistentProcessors(['persistent_processor1']);
        $this->extension->setPersistentProcessors(['persistent_processor2']);

        // verify that clearers are called only for non-persistent processors
        $this->clearer1->expects(static::once())->method('clear')->with(static::identicalTo($this->logger));
        $this->clearer2->expects(static::once())->method('clear')->with(static::identicalTo($this->logger));

        $this->extension->onPostReceived($this->createMessageContextForProcessor('non_persistent_processor'));

        $this->clearer1->expects(static::never())->method('clear');
        $this->clearer2->expects(static::never())->method('clear');

        // processing in different order to verify that subsequent calls to setPersistentProcessors
        // did not wipe out previously set persistent processors
        $this->extension->onPostReceived($this->createMessageContextForProcessor('persistent_processor2'));
        $this->extension->onPostReceived($this->createMessageContextForProcessor('persistent_processor1'));
    }

    public function testSetChainExtensionSetsItOnlyOnChainExtensionAwareInterfaceClearers(): void
    {
        $chainExtension = $this->createMock(ExtensionInterface::class);

        $this->clearer1->expects(static::never())->method('setChainExtension');
        $this->clearer2->expects(static::never())->method('setChainExtension');
        $this->chainExtensionAwareClearer->expects(static::once())
            ->method('setChainExtension')
            ->with(static::identicalTo($chainExtension));

        $this->extension->setChainExtension($chainExtension);
    }

    private function createMessageContextForProcessor(string $processorName): Context
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessorName($processorName);
        $context->setLogger($this->logger);

        return $context;
    }
}
