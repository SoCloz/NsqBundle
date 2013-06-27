<?php

namespace Socloz\NsqBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeDelayedCommand extends BaseCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(array())
            ->setName('socloz:nsq:delayed:consume')
            ->addOption('timeout', "t", InputOption::VALUE_OPTIONAL, 'Timeout', 0)
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->getContainer()
            ->get('socloz.nsq')
            ->consumeDelayedMessagesTopic((int) $input->getOption('timeout'))
        ;
    }
}
