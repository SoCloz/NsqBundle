<?php

namespace Socloz\NsqBundle\Logger;

use nsqphp\Logger\LoggerInterface;
use Monolog\Logger;

class MonologAdapter implements LoggerInterface
{
    /**
     *
     * @var Logger
     */
    private $logger;

    /**
     *
     * @param \Monolog\Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function error($msg)
    {
        $this->logger->err($msg);
    }

    /**
     * {@inheritDoc}
     */
    public function warn($msg)
    {
        $this->logger->warn($msg);
    }


    /**
     * {@inheritDoc}
     */
    public function info($msg)
    {
        $this->logger->info($msg);
    }

    /**
     * {@inheritDoc}
     */
    public function debug($msg)
    {
        $this->logger->debug($msg);
    }
}
