<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Configuration;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\DbalTransportFactory;
use Oro\Component\MessageQueue\Client\NoopMessageProcessor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $factories = [];
        $configuration = new Configuration($factories, 'prod');

        $treeBuilder = $configuration->getConfigTreeBuilder();
        self::assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testProcessConfiguration(): void
    {
        $factories = [
            'dbal' => new DbalTransportFactory(),
        ];

        $configuration = new Configuration($factories, 'prod');
        $processor = new Processor();

        $expected = [
            'persistent_services' => [],
            'persistent_processors' => [],
            'security_agnostic_topics' => [],
            'security_agnostic_processors' => [],
            'time_before_stale' => [
                'jobs' => [],
            ],
            'consumer' => [
                'heartbeat_update_period' => 15,
            ],
            'transport' => [
                'dbal' => [
                    'connection' => 'message_queue',
                    'table' => 'oro_message_queue',
                    'pid_file_dir' => '/tmp/oro-message-queue',
                    'consumer_process_pattern' => ':consume',
                    'polling_interval' => 1000,
                ],
            ],
            'client' => [
                'traceable_producer' => false,
                'prefix' => 'oro',
                'default_destination' => 'default',
                'default_topic' => 'default',
                'redelivery' => [
                    'enabled' => true,
                    'delay_time' => 10,
                ],
                'noop_status' => NoopMessageProcessor::REQUEUE,
            ],
        ];

        self::assertEquals(
            $expected,
            $processor->processConfiguration($configuration, [
                'oro_message_queue' => [
                    'persistent_services' => [],
                    'persistent_processors' => [],
                    'security_agnostic_topics' => [],
                    'security_agnostic_processors' => [],
                    'time_before_stale' => [],
                    'consumer' => [],
                    'transport' => [],
                    'client' => [],
                ],
            ])
        );
    }

    /**
     * @dataProvider processConfigurationDataProvider
     *
     * @param string $environment
     * @param string $expectedNoopStatus
     */
    public function testProcessConfigurationUsesDefaultNoopStatus(string $environment, string $expectedNoopStatus): void
    {
        $configuration = new Configuration([], $environment);
        $processor = new Processor();

        $processedConfig = $processor->processConfiguration($configuration, []);
        self::assertEquals($expectedNoopStatus, $processedConfig['client']['noop_status']);
    }

    public function processConfigurationDataProvider(): array
    {
        return [
            'env is prod' => [
                'environment' => 'prod',
                'expectedNoopStatus' => NoopMessageProcessor::REQUEUE,
            ],
            'env is dev' => [
                'environment' => 'dev',
                'expectedNoopStatus' => NoopMessageProcessor::THROW_EXCEPTION,
            ],
            'env is test' => [
                'environment' => 'test',
                'expectedNoopStatus' => NoopMessageProcessor::THROW_EXCEPTION,
            ],
        ];
    }

    /**
     * @dataProvider processConfigurationWithAllowedNoopStatusDataProvider
     *
     * @param string $noopStatus
     */
    public function testProcessConfigurationWithAllowedNoopStatus(string $noopStatus): void
    {
        $configuration = new Configuration([], 'prod');
        $processor = new Processor();

        $processedConfig = $processor->processConfiguration($configuration, [
            'oro_message_queue' => [
                'client' => ['noop_status' => $noopStatus],
            ],
        ]);

        self::assertEquals($noopStatus, $processedConfig['client']['noop_status']);
    }

    public function processConfigurationWithAllowedNoopStatusDataProvider(): array
    {
        return [
            [NoopMessageProcessor::ACK],
            [NoopMessageProcessor::REJECT],
            [NoopMessageProcessor::REQUEUE],
            [NoopMessageProcessor::THROW_EXCEPTION],
        ];
    }

    public function testProcessConfigurationThrowsExceptionWhenInvalidNoopStatus(): void
    {
        $configuration = new Configuration([], 'prod');
        $processor = new Processor();

        $this->expectExceptionMessage(
            'The value "invalid_status" is not allowed for path "oro_message_queue.client.noop_status"'
        );

        $processor->processConfiguration($configuration, [
            'oro_message_queue' => [
                'client' => ['noop_status' => 'invalid_status'],
            ],
        ]);
    }
}
