<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\NullTransportFactory;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestBufferedMessageProducer;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\NullDriver;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;

class OroMessageQueueExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $configs = [];

        $driverFactoryDefinition = new Definition(\stdClass::class, [[]]);
        $configDefinition = new Definition();
        $redeliveryExtension = new Definition(\stdClass::class, ['', 0]);
        $signalExtension = new Definition();

        $container = $this->getContainerMock();
        $container->expects($this->exactly(2))
            ->method('getParameter')
            ->will($this->returnValueMap([
                ['message_queue_transport', 'null'],
                ['kernel.environment', 'prod'],
            ]));
        $container->expects($this->exactly(4))
            ->method('getDefinition')
            ->will($this->returnValueMap([
                ['oro_message_queue.client.driver_factory', $driverFactoryDefinition],
                ['oro_message_queue.client.config', $configDefinition],
                ['oro_message_queue.consumption.redelivery_message_extension', $redeliveryExtension],
                ['oro_message_queue.consumption.signal_extension', $signalExtension],
            ]));
        $container->expects($this->never())
            ->method('register');

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());
        $extension->load($configs, $container);

        $this->assertParametersLoaded($this->getRequiredParameters());
        $this->assertDefinitionsLoaded($this->getRequiredDefinitions());
        $this->assertExtensionConfigsLoaded([]);

        $this->assertEquals([
            NullConnection::class => NullDriver::class,
            DbalConnection::class => DbalDriver::class,
            DbalLazyConnection::class => DbalDriver::class,
        ], $driverFactoryDefinition->getArgument(0));
        $this->assertEquals([
            'oro',
            'oro_message_queue.client.route_message_processor',
            'default',
            'default',
            'default',
        ], $configDefinition->getArguments());
        $this->assertEquals(10, $redeliveryExtension->getArgument(1));
        $this->assertEquals(['oro_message_queue.consumption.extension' => [[]]], $redeliveryExtension->getTags());
        $this->assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $signalExtension->getTags()
        );
    }

    public function testLoadWithDisabledRedelivery()
    {
        $configs = ['oro_message_queue' => ['client' => [
            'redelivery' => [
                'enabled' => false
            ]
        ]]];

        $driverFactoryDefinition = new Definition(\stdClass::class, [[]]);
        $configDefinition = new Definition();
        $signalExtension = new Definition();

        $container = $this->getContainerMock();
        $container->expects($this->exactly(2))
            ->method('getParameter')
            ->will($this->returnValueMap([
                ['message_queue_transport', 'null'],
                ['kernel.environment', 'prod'],
            ]));
        $container->expects($this->exactly(3))
            ->method('getDefinition')
            ->will($this->returnValueMap([
                ['oro_message_queue.client.driver_factory', $driverFactoryDefinition],
                ['oro_message_queue.client.config', $configDefinition],
                ['oro_message_queue.consumption.signal_extension', $signalExtension],
            ]));
        $container->expects($this->never())
            ->method('register');

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());
        $extension->load($configs, $container);

        $this->assertParametersLoaded($this->getRequiredParameters());
        $this->assertDefinitionsLoaded($this->getRequiredDefinitions());
        $this->assertExtensionConfigsLoaded([]);

        $this->assertEquals([
            NullConnection::class => NullDriver::class,
            DbalConnection::class => DbalDriver::class,
            DbalLazyConnection::class => DbalDriver::class,
        ], $driverFactoryDefinition->getArgument(0));
        $this->assertEquals([
            'oro',
            'oro_message_queue.client.route_message_processor',
            'default',
            'default',
            'default',
        ], $configDefinition->getArguments());
        $this->assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $signalExtension->getTags()
        );
    }

    public function testLoadWithClientConfig()
    {
        $configs = ['oro_message_queue' => ['client' => [
            'prefix' => 'test_',
            'router_processor' => 'Router/Processor',
            'router_destination' => 'router.destination',
            'default_destination' => 'default.destination',
            'default_topic' => 'default.topic',
        ]]];

        $driverFactoryDefinition = new Definition(\stdClass::class, [[]]);
        $configDefinition = new Definition();
        $redeliveryExtension = new Definition(\stdClass::class, ['', 0]);
        $signalExtension = new Definition();

        $container = $this->getContainerMock();
        $container->expects($this->exactly(2))
            ->method('getParameter')
            ->will($this->returnValueMap([
                ['message_queue_transport', 'null'],
                ['kernel.environment', 'prod'],
            ]));
        $container->expects($this->exactly(4))
            ->method('getDefinition')
            ->will($this->returnValueMap([
                ['oro_message_queue.client.driver_factory', $driverFactoryDefinition],
                ['oro_message_queue.client.config', $configDefinition],
                ['oro_message_queue.consumption.redelivery_message_extension', $redeliveryExtension],
                ['oro_message_queue.consumption.signal_extension', $signalExtension],
            ]));
        $container->expects($this->never())
            ->method('register');

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());
        $extension->load($configs, $container);

        $this->assertParametersLoaded($this->getRequiredParameters());
        $this->assertDefinitionsLoaded(array_merge(
            $this->getRequiredDefinitions(),
            $this->getClientDefinitions()
        ));
        $this->assertExtensionConfigsLoaded([]);
        $this->assertEquals([
            NullConnection::class => NullDriver::class,
            DbalConnection::class => DbalDriver::class,
            DbalLazyConnection::class => DbalDriver::class,
        ], $driverFactoryDefinition->getArgument(0));
        $this->assertEquals([
            'test_',
            'Router/Processor',
            'router.destination',
            'default.destination',
            'default.topic',
        ], $configDefinition->getArguments());
        $this->assertEquals(10, $redeliveryExtension->getArgument(1));
        $this->assertEquals(['oro_message_queue.consumption.extension' => [[]]], $redeliveryExtension->getTags());
        $this->assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $signalExtension->getTags()
        );
    }

    public function testLoadWithClientConfigAndTraceableProducer()
    {
        $configs = ['oro_message_queue' => ['client' => [
            'prefix' => 'test_',
            'router_processor' => 'Router/Processor',
            'router_destination' => 'router.destination',
            'default_destination' => 'default.destination',
            'default_topic' => 'default.topic',
            'redelivery' => ['delay_time' => 2119],
            'traceable_producer' => true,
        ]]];

        $driverFactoryDefinition = new Definition(\stdClass::class, [[]]);
        $configDefinition = new Definition();
        $redeliveryExtension = new Definition(\stdClass::class, ['', 0]);
        $signalExtension = new Definition();
        $traceableMessageProducerDefinition = new Definition(TraceableMessageProducer::class);

        $container = $this->getContainerMock();
        $container->expects($this->exactly(2))
            ->method('getParameter')
            ->will($this->returnValueMap([
                ['message_queue_transport', 'null'],
                ['kernel.environment', 'prod'],
            ]));
        $container->expects($this->exactly(4))
            ->method('getDefinition')
            ->will($this->returnValueMap([
                ['oro_message_queue.client.driver_factory', $driverFactoryDefinition],
                ['oro_message_queue.client.config', $configDefinition],
                ['oro_message_queue.consumption.redelivery_message_extension', $redeliveryExtension],
                ['oro_message_queue.consumption.signal_extension', $signalExtension],
            ]));
        $container->expects($this->once())
            ->method('register')
            ->with('oro_message_queue.client.traceable_message_producer', TraceableMessageProducer::class)
            ->willReturnCallback(function ($id) use ($container, $traceableMessageProducerDefinition) {
                $container->setDefinition($id, $traceableMessageProducerDefinition);

                return $traceableMessageProducerDefinition;
            });

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());
        $extension->load($configs, $container);

        $this->assertParametersLoaded($this->getRequiredParameters());
        $this->assertDefinitionsLoaded(array_merge(
            $this->getRequiredDefinitions(),
            $this->getClientDefinitions(),
            ['oro_message_queue.client.traceable_message_producer']
        ));
        $this->assertExtensionConfigsLoaded([]);
        $this->assertEquals([
            NullConnection::class => NullDriver::class,
            DbalConnection::class => DbalDriver::class,
            DbalLazyConnection::class => DbalDriver::class,
        ], $driverFactoryDefinition->getArgument(0));
        $this->assertEquals([
            'test_',
            'Router/Processor',
            'router.destination',
            'default.destination',
            'default.topic',
        ], $configDefinition->getArguments());
        $this->assertEquals(2119, $redeliveryExtension->getArgument(1));
        $this->assertEquals(['oro_message_queue.consumption.extension' => [[]]], $redeliveryExtension->getTags());
        $this->assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $signalExtension->getTags()
        );
        $this->assertEquals(
            'oro_message_queue.client.traceable_message_producer.inner',
            (string) $traceableMessageProducerDefinition->getArgument(0)
        );
    }

    public function testLoadWithConsumerConfig()
    {
        $configs = ['oro_message_queue' => ['consumer' => [
            'heartbeat_update_period' => 1823,
        ]]];

        $driverFactoryDefinition = new Definition(\stdClass::class, [[]]);
        $configDefinition = new Definition();
        $redeliveryExtension = new Definition(\stdClass::class, ['', 0]);
        $signalExtension = new Definition();

        $container = $this->getContainerMock();
        $container->expects($this->exactly(2))
            ->method('getParameter')
            ->will($this->returnValueMap([
                ['message_queue_transport', 'null'],
                ['kernel.environment', 'prod'],
            ]));
        $container->expects($this->exactly(4))
            ->method('getDefinition')
            ->will($this->returnValueMap([
                ['oro_message_queue.client.driver_factory', $driverFactoryDefinition],
                ['oro_message_queue.client.config', $configDefinition],
                ['oro_message_queue.consumption.redelivery_message_extension', $redeliveryExtension],
                ['oro_message_queue.consumption.signal_extension', $signalExtension],
            ]));
        $container->expects($this->never())
            ->method('register');

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());
        $extension->load($configs, $container);

        $this->assertParametersLoaded(array_merge(
            $this->getRequiredParameters(),
            ['oro_message_queue.consumer_heartbeat_update_period']
        ));
        $this->assertDefinitionsLoaded($this->getRequiredDefinitions());
        $this->assertExtensionConfigsLoaded([]);

        $this->assertEquals([
            NullConnection::class => NullDriver::class,
            DbalConnection::class => DbalDriver::class,
            DbalLazyConnection::class => DbalDriver::class,
        ], $driverFactoryDefinition->getArgument(0));
        $this->assertEquals([
            'oro',
            'oro_message_queue.client.route_message_processor',
            'default',
            'default',
            'default',
        ], $configDefinition->getArguments());
        $this->assertEquals(10, $redeliveryExtension->getArgument(1));
        $this->assertEquals(['oro_message_queue.consumption.extension' => [[]]], $redeliveryExtension->getTags());
        $this->assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $signalExtension->getTags()
        );
    }

    public function testLoadTestEnvironment()
    {
        $configs = [];

        $driverFactoryDefinition = new Definition(\stdClass::class, [[]]);
        $configDefinition = new Definition();
        $redeliveryExtension = new Definition(\stdClass::class, ['', 0]);
        $signalExtension = new Definition();
        $testBufferedMessageProducer = new Definition();

        $container = $this->getContainerMock();
        $container->expects($this->exactly(2))
            ->method('getParameter')
            ->will($this->returnValueMap([
                ['message_queue_transport', 'null'],
                ['kernel.environment', 'test'],
            ]));
        $container->expects($this->exactly(5))
            ->method('getDefinition')
            ->will($this->returnValueMap([
                ['oro_message_queue.client.driver_factory', $driverFactoryDefinition],
                ['oro_message_queue.client.config', $configDefinition],
                ['oro_message_queue.consumption.redelivery_message_extension', $redeliveryExtension],
                ['oro_message_queue.consumption.signal_extension', $signalExtension],
                ['oro_message_queue.client.buffered_message_producer', $testBufferedMessageProducer],
            ]));
        $container->expects($this->never())
            ->method('register');

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());
        $extension->load($configs, $container);

        $this->assertParametersLoaded($this->getRequiredParameters());
        $this->assertDefinitionsLoaded(array_merge($this->getRequiredDefinitions(), [
            'oro_message_queue.async.unique_message_processor.stub',
            'oro_message_queue.async.dependent_message_processor.stub',
        ]));
        $this->assertExtensionConfigsLoaded([]);

        $this->assertEquals([
            NullConnection::class => NullDriver::class,
            DbalConnection::class => DbalDriver::class,
            DbalLazyConnection::class => DbalDriver::class,
        ], $driverFactoryDefinition->getArgument(0));
        $this->assertEquals([
            'oro',
            'oro_message_queue.client.route_message_processor',
            'default',
            'default',
            'default',
        ], $configDefinition->getArguments());
        $this->assertEquals(10, $redeliveryExtension->getArgument(1));
        $this->assertEquals(['oro_message_queue.consumption.extension' => [[]]], $redeliveryExtension->getTags());
        $this->assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $signalExtension->getTags()
        );
        $this->assertEquals(TestBufferedMessageProducer::class, $testBufferedMessageProducer->getClass());
        $this->assertTrue($testBufferedMessageProducer->isPublic());
    }

    /**
     * @dataProvider invalidConfigurationDataProvider
     *
     * @param string $transport
     * @param string $expectedExceptionMessage
     */
    public function testLoadInvalidConfigurationException($transport, $expectedExceptionMessage)
    {
        $driverFactoryDefinition = new Definition(\stdClass::class, [[]]);
        $configDefinition = new Definition();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $container = $this->getContainerMock();
        $container->expects($this->once())
            ->method('getParameter')
            ->with('message_queue_transport')
            ->willReturn($transport);

        $container->expects($this->exactly(2))
            ->method('getDefinition')
            ->will($this->returnValueMap([
                ['oro_message_queue.client.driver_factory', $driverFactoryDefinition],
                ['oro_message_queue.client.config', $configDefinition],
            ]));

        $extension = new OroMessageQueueExtension();
        $extension->load([], $container);
    }

    /**
     * @return array
     */
    public function invalidConfigurationDataProvider()
    {
        return [
            'null transport' => [
                'transport' => null,
                'expectedExceptionMessage' => 'Message queue transport key is not defined.'
            ],
            'empty transport' => [
                'transport' => '',
                'expectedExceptionMessage' => 'Message queue transport key is not defined.'
            ],
            'invalid transport' => [
                'transport' => 'test',
                'expectedExceptionMessage' => 'Message queue transport with key "test" is not found.'
            ]
        ];
    }

    /**
     * @return array
     */
    private function getRequiredParameters()
    {
        return [
            // services.yml
            'oro_message_queue.maintenance.idle_time',
            'oro_message_queue.consumption.interrupt_filepath',
            // job.yml
            'oro_message_queue.job.unique_job_table_name',
        ];
    }

    /**
     * @return array
     */
    private function getRequiredDefinitions()
    {
        $definitions = [
            // services.yml
            'oro_message_queue.consumption.extensions',
            'oro_message_queue.consumption.container_reset_extension',
            'oro_message_queue.consumption.docrine_ping_connection_extension',
            'oro_message_queue.consumption.docrine_clear_identity_map_extension',
            'oro_message_queue.consumption.maintenance_extension',
            'oro_message_queue.consumption.interrupt_consumption_extension',
            'oro_message_queue.consumption.consumer_heartbeat_extension',
            'oro_message_queue.consumption.security_aware_extension',
            'oro_message_queue.consumption.locale_extension',
            'oro_message_queue.consumption.clear_logger_extension',
            'oro_message_queue.consumption.database_connections_clearer',
            'oro_message_queue.consumption.container_clearer',
            'oro_message_queue.consumption.garbage_collector_clearer',
            'oro_message_queue.consumption.queue_consumer',
            'oro_message_queue.listener.update_schema',
            'oro_message_queue.consumption.cache_state',
            'oro_message_queue.consumption.cache_state_driver.dbal',
            'oro_message_queue.consumption.consumer_heartbeat',
            'oro_message_queue.consumption.consumer_state_driver.dbal',
            'oro_message_queue.listener.authentication',
            'oro_message_queue.topic.message_queue_heartbeat',
            'oro_message_queue.event_listener.console_error',
            // log.yml
            'oro_message_queue.log.consumer_state',
            'oro_message_queue.log.consumption_extension',
            'oro_message_queue.log.job_extension',
            'oro_message_queue.log.message_processor_class_provider',
            'oro_message_queue.log.message_to_array_converter',
            'oro_message_queue.log.message_to_array_converter.base',
            'oro_message_queue.log.message_to_array_converter.dbal',
            'oro_message_queue.log.processor.restore_original_channel',
            'oro_message_queue.log.processor.add_consumer_state',
            'oro_message_queue.log.handler.console',
            'oro_message_queue.log.handler.console_error',
            'oro_message_queue.log.handler.resend_job',
            // job.yml
            'oro_message_queue.job.configuration_provider',
            'oro_message_queue.job.storage',
            'oro_message_queue.job.unique_job_handler',
            'oro_message_queue.job.processor',
            'oro_message_queue.job.runner',
            'oro_message_queue.job.extensions',
            'oro_message_queue.job.root_job_status_calculator',
            'oro_message_queue.checker.job_status_checker',
            'oro_message_queue.status_calculator.abstract_status_calculator',
            'oro_message_queue.status_calculator.collection_calculator',
            'oro_message_queue.status_calculator.query_calculator',
            'oro_message_queue.status_calculator.status_calculator_resolver',
            'oro_message_queue.job.calculate_root_job_status_processor',
            'oro_message_queue.job.dependent_job_processor',
            'oro_message_queue.job.dependent_job_service',
            'oro_message_queue.job.grid.root_job_action_configuration',
            'oro_message_queue.job.security_aware_extension',
            'oro_message_queue.job.root_job_status_extension',
            // commands.yml
            'Oro\Bundle\MessageQueueBundle\Command\CleanupCommand',
            'Oro\Bundle\MessageQueueBundle\Command\ClientConsumeMessagesCommand',
            'Oro\Bundle\MessageQueueBundle\Command\ConsumerHeartbeatCommand',
            'Oro\Bundle\MessageQueueBundle\Command\TransportConsumeMessagesCommand',
            // defined in extension
            'oro_message_queue.transport.null.connection',
            'oro_message_queue.consumption.redelivery_message_extension',
            'oro_message_queue.consumption.signal_extension',
        ];

        return $definitions;
    }

    /**
     * @return array
     */
    private function getClientDefinitions()
    {
        return [
            // client.yml
            'oro_message_queue.client.config',
            'oro_message_queue.client.driver_factory',
            'oro_message_queue.client.security_aware_driver_factory',
            'oro_message_queue.client.driver',
            'oro_message_queue.client.message_producer',
            'oro_message_queue.client.router',
            'oro_message_queue.client.route_message_processor',
            'oro_message_queue.client.message_processor_registry',
            'oro_message_queue.client.meta.topic_meta_registry',
            'oro_message_queue.client.meta.destination_meta_registry',
            'oro_message_queue.client.delegate_message_processor',
            'oro_message_queue.client.extension.create_queue',
            'oro_message_queue.client.queue_consumer',
            'oro_message_queue.client.created_queues',
            'oro_message_queue.client.meta.topics_command',
            'oro_message_queue.client.meta.destinations_command',
            'oro_message_queue.client.create_queues_command',
            'oro_message_queue.profiler.message_queue_collector',
            'oro_message_queue.client.buffered_message_producer',
            'oro_message_queue.client.dbal_transaction_watcher',
        ];
    }
}
