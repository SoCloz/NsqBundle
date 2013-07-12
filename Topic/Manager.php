<?php

namespace Socloz\NsqBundle\Topic;

use nsqphp\Lookup\Nsqlookupd;
use nsqphp\Lookup\FixedHosts;
use nsqphp\Logger\LoggerInterface;

use Socloz\NsqBundle\Consumer\ConsumerInterface;
use Socloz\NsqBundle\Delayed\Topic as DelayedMessagesTopic;

class Manager
{
    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var string
     */
    private $delayedMessagesTopicName;

    /**
     *
     * @var Nsqlookupd
     */
    private $lookupd;

    /**
     *
     * @var string
     */
    private $topics = array();

    /**
     *
     * @var boolean
     */
    private $stubMode = false;

    /**
     *
     * @param string $delayedMessagesTopicName
     * @param \nsqphp\Lookup\Nsqlookupd $lookupd
     * @param \nsqphp\Logger\LoggerInterface $logger
     */
    public function __construct($delayedMessagesTopicName, Nsqlookupd $lookupd = null, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->delayedMessagesTopicName = $delayedMessagesTopicName;
        $this->lookupd = $lookupd;
    }

    /**
     *
     * @param boolean $b
     */
    public function setStubMode($b)
    {
        $this->stubMode = (bool) $b;
    }

    public function setDelayedMessagesTopic(array $conf)
    {
        $topic = new DelayedMessagesTopic(
            $this->delayedMessagesTopicName,
            array($conf['publish_to']),
            new FixedHosts(array($conf['publish_to'])),
            $this->logger
        );
        if (isset($conf['requeue_strategy'])) {
            $this->setRequeueStrategy($topic, $conf['requeue_strategy']);
        }
        $this->topics[$topic->getName()] = $topic;
    }

    public function getDelayedMessagesTopic()
    {
        return $this->getTopic($this->delayedMessagesTopicName);
    }

    /**
     *
     * @param string $name
     * @param array $conf
     */
    public function setTopic($name, array $conf)
    {
        if ($this->stubMode) {
            $topic = new Stub($name);
        } else {
            $lookupd = $this->lookupd ?: new FixedHosts($conf['publish_to']);
            $topic = new Topic($name, $conf['publish_to'], $lookupd, $this->logger);
            $dmt = $this->getDelayedMessagesTopic();
            if ($dmt) {
                $topic->setDelayedMessagesTopic($dmt);
            }
            if (isset($conf['requeue_strategy'])) {
                $this->setRequeueStrategy($topic, $conf['requeue_strategy']);
            }
        }
        $this->topics[$topic->getName()] = $topic;
    }

    private function setRequeueStrategy(Topic $topic, $conf)
    {
        $topic->setRequeueStrategy(
            new \nsqphp\RequeueStrategy\DelaysList(
                $conf['max_attempts'],
                $conf['delays']
            )
        );
    }

    /**
     *
     * @param string $name
     * @return boolean
     */
    public function hasTopic($name)
    {
        return $this->getTopic($name) !== null;
    }

    /**
     *
     * @param string $name
     * @return Topic|null
     */
    public function getTopic($name)
    {
        if (isset($this->topics[$name])) {
            return $this->topics[$name];
        }
        return null;
    }

    /**
     *
     * @param string $name
     * @param string $channel
     * @param \Socloz\NsqBundle\ConsumerInterface $consumer
     */
    public function setTopicConsumer($name, $channel, ConsumerInterface $consumer)
    {
        if ($this->hasTopic($name)) {
            $this->getTopic($name)->setConsumer($channel, $consumer);
        }
    }

    public function consumeDelayedMessagesTopic($timeout = 0)
    {
        $topic = $this->getDelayedMessagesTopic();
        if (!$topic) {
            throw new \Exception(
                "Delayed messages topic '$this->delayedMessagesTopicName' is not set"
            );
        }
        $topic->consume(array(), $timeout);
    }
}
