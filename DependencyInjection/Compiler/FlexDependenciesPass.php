<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FlexDependenciesPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $this->cleanMaintenanceBundleDependency($container);
        $this->cleanSecurityBundleDependency($container);
        $this->cleanSyncBundleDependency($container);
        $this->cleanPlatformBundleDependency($container);
        $this->cleanLocaleBundleDependency($container);
        $this->cleanEntityExtendBundleDependency($container);
        $this->cleanUserBundleDependency($container);
        $this->cleanCronBundleDependency($container);
        $this->cleanConfigBundleDependency($container);
    }

    private function cleanMaintenanceBundleDependency(ContainerBuilder $container)
    {
        if (!class_exists('\Oro\Bundle\MaintenanceBundle\OroMaintenanceBundle')) {
            $this->remove('oro_message_queue.consumption.maintenance_extension', $container);
        }
    }

    private function cleanSecurityBundleDependency(ContainerBuilder $container)
    {
        if (!class_exists('\Oro\Bundle\SecurityBundle\OroSecurityBundle')) {
            $this->remove('oro_message_queue.consumption.security_aware_extension', $container);
            $this->remove('oro_message_queue.job.security_aware_extension', $container);
            $this->remove('oro_message_queue.client.security_aware_driver_factory', $container);
        }
    }

    private function cleanSyncBundleDependency(ContainerBuilder $container)
    {
        if (!class_exists('\Oro\Bundle\SyncBundle\OroSyncBundle')) {
            $this->remove('oro_message_queue.topic.message_queue_heartbeat', $container);
        }
    }

    private function cleanPlatformBundleDependency(ContainerBuilder $container)
    {
        if (!class_exists('\Oro\Bundle\PlatformBundle\OroPlatformBundle')) {
            $this->remove('oro_message_queue.platform.optional_listener_extension', $container);
            $this->remove('oro_message_queue.platform.optional_listener_driver_factory', $container);
        }
    }

    private function cleanLocaleBundleDependency(ContainerBuilder $container)
    {
        if (!class_exists('\Oro\Bundle\LocaleBundle\OroLocaleBundle')) {
            $this->remove('oro_message_queue.consumption.locale_extension', $container);
        }
    }

    private function cleanEntityExtendBundleDependency(ContainerBuilder $container)
    {
        if (!class_exists('\Oro\Bundle\EntityExtendBundle\OroEntityExtendBundle')) {
            $this->remove('oro_message_queue.listener.update_schema', $container);
        }
    }

    private function cleanUserBundleDependency(ContainerBuilder $container)
    {
        if (!class_exists('\Oro\Bundle\UserBundle\OroUserBundle')) {
            $this->remove('oro_message_queue.listener.authentication', $container);
        }
    }

    private function cleanCronBundleDependency(ContainerBuilder $container)
    {
        if (!class_exists('\Oro\Bundle\CronBundle\OroCronBundle')) {
            $this->remove('Oro\Bundle\MessageQueueBundle\Command\CleanupCommand', $container);
            $this->remove('Oro\Bundle\MessageQueueBundle\Command\ConsumerHeartbeatCommand', $container);
        }
    }

    private function cleanConfigBundleDependency(ContainerBuilder $container)
    {
        if (!class_exists('\Oro\Bundle\ConfigBundle\OroConfigBundle')) {
            $this->remove('oro_message_queue.async.change_config', $container);
        }
    }

    private function remove(string $string, ContainerBuilder $container)
    {
        if ($container->hasDefinition($string)) {
            $container->removeDefinition($string);
        }
    }
}