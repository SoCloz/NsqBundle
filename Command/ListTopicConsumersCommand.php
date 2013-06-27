<?php

namespace Socloz\NsqBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListTopicConsumersCommand extends BaseCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(array())
            ->addArgument('topic', InputArgument::REQUIRED, 'Topic')
            ->setName('socloz:nsq:topic:list')
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
        foreach ($topic->getConsumers() as $channel => $consumer) {
            $output->writeln(sprintf(
                " - Channel '%s': %s",
                $channel,
                get_class($consumer)
            ));
        }
    }
}
