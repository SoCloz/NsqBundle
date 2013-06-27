<?php

namespace Socloz\NsqBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Socloz\NsqBundle\DependencyInjection\ConsumerCompilerPass;

class SoclozNsqBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ConsumerCompilerPass());
    }
}
