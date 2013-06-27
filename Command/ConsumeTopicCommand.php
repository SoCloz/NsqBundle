<?php

namespace Socloz\NsqBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeTopicCommand extends BaseCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(array())
            ->addArgument('topic', InputArgument::REQUIRED, 'Topic')
            // @todo handle comma separated "white" list of channels
            ->addOption('channel', "c", InputOption::VALUE_OPTIONAL, 'Channel')
            ->addOption('timeout', "t", InputOption::VALUE_OPTIONAL, 'Timeout', 0)
            ->setName('socloz:nsq:topic:consume')
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $topicName = $input->getArgument('topic');
        $topic = $this->getContainer()->get('socloz.nsq')->getTopic($topicName);
        if (!$topic) {
            throw new \Exception("Unknown topic $topicName");
        }
        $channels = array();
        if ($input->getOption('channel')) {
            $channels[] = $input->getOption('channel');
        }
        $topic->consume($channels, (int) $input->getOption('timeout'));
    }
}
