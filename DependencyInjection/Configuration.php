<?php

namespace Socloz\NsqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('socloz_nsq');
        $node = $rootNode
            ->children()
                ->arrayNode('lookupd_hosts')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('delayed_messages_topic')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('publish_to')
                            ->defaultValue('localhost')
                        ->end()
                        ->arrayNode('requeue_strategy')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('max_attempts')
                                    ->defaultValue('5')
                                ->end()
                                ->arrayNode('delays')
                                    ->prototype('scalar')
                                    ->end()
                                    ->defaultValue(array('10000'))
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('topics')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('publish_to')
                                ->requiresAtLeastOneElement()
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->arrayNode('requeue_strategy')
                                ->children()
                                    ->scalarNode('max_attempts')
                                    ->end()
                                    ->arrayNode('delays')
                                        ->prototype('scalar')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('consumers')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
