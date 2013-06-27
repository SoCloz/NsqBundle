<?php

namespace Socloz\NsqBundle\DependencyInjection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ConsumerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('socloz.nsq')) {
            return;
        }
        $definition = $container->getDefinition('socloz.nsq');
        $taggedServices = $container->findTaggedServiceIds(
            'socloz.nsq.consumer'
        );
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addQueueConsumer',
                    array(
                        $attributes['topic'],
                        $attributes['channel'],
                        new Reference($id)
                    )
                );
            }
        }
    }
}
