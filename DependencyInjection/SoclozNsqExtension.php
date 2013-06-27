<?php

namespace Socloz\NsqBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Socloz\NsqBundle\Topic\Topic;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SoclozNsqExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yml');
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $def = $container->getDefinition('socloz.nsq');
        if ($config['lookupd_hosts']) {
            $container->setDefinition(
                'socloz.nsq.lookup',
                new Definition(
                    'nsqphp\Lookup\Nsqlookupd',
                    array($config['lookupd_hosts'])
                )
            );
        }
        $def->addMethodCall('setDelayedMessagesTopic', $config['delayed_messages_topic']);
        foreach ($config['topics'] as $name => $conf) {
            $def->addMethodCall('setTopic', array($name, $conf));
            if ($conf['consumers']) {
                foreach ($conf['consumers'] as $channel => $service) {
                    $def->addMethodCall('addTopicConsumer', array(
                        $name,
                        $channel,
                        new Reference($service)
                    ));
                }
            }
        }
    }
}
