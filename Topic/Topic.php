<?php

namespace Socloz\NsqBundle\Topic;

use nsqphp\nsqphp;
use nsqphp\Logger\LoggerInterface;
use nsqphp\Message\Message;
use nsqphp\Lookup\LookupInterface;
use nsqphp\RequeueStrategy\RequeueStrategyInterface;

use Socloz\NsqBundle\Consumer\ConsumerInterface;
use Socloz\NsqBundle\Delayed\Topic as DelayedMessagesTopic;
use Socloz\NsqBundle\Delayed\Message as DelayedMessage;

class Topic
{
    /**
     *
     * @var string
     */
    private $name;

    /**
     *
     * @var nsqphp
     */
    private $nsq;

    /**
     *
     * @var array
     */
    private $publishToHosts = array();

    /**
     *
     * @var boolean
     */
    private $publishToDone = false;

    /**
     *
     * @var DelayedMessagesTopic
     */
    private $delayedMessagesTopic;

    /**
     *
     * @var ConsumerInterface
     */
    private $consumers = array();

    /**
     *
     * @var array
     */
    private $subscribedChannels = array();

    public function __construct($name, array $hosts, LookupInterface $lookup = null, LoggerInterface $logger = null)
    {
        $this->name = $name;
        $this->nsq = new nsqphp($lookup, null, null, $logger);
        $this->publishToHosts = $hosts;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setRequeueStrategy(RequeueStrategyInterface $rs)
    {
        $this->nsq->setRequeueStrategy($rs);
    }

    public function setConsumer($channel, ConsumerInterface $consumer)
    {
        if (isset($this->consumers[$channel])) {
            throw new \Exception(
                "Topic $this->name already have a registered consumer for channel $channel"
            );
        }
        $this->consumers[$channel] = $consumer;
    }

    public function getConsumers()
    {
        return $this->consumers;
    }

    public function setDelayedMessagesTopic(DelayedMessagesTopic $topic)
    {
        $this->delayedMessagesTopic = $topic;
    }

    public function publish($payload, $delay = 0)
    {
        if ($delay > 0) {
            if (!$this->delayedMessagesTopic) {
                throw new \Exception(
                    "Cannot handle delayed message '$payload' with no delayed messages's topic"
                );
            }
            $this->delayedMessagesTopic->publish(new DelayedMessage(
                $this->name,
                $payload,
                $delay
            ));
            return;
        }
        $this->doPublish($payload);
    }

    protected function doPublish($payload)
    {
        if (!$this->publishToDone) {
            $this->nsq->publishTo($this->publishToHosts);
            $this->publishToDone = true;
        }
        $message = new Message((string)$payload);
        $this->nsq->publish($this->name, $message);
    }

    public function consume(array $channels = array(), $timeout = null)
    {
        if (!$this->consumers) {
            throw new \Exception("Topic $this->name has no consumers");
        }
        $topic = $this->name;
        foreach ($this->consumers as $channel => $consumer) {
            if ($channels && !in_array($channel, $channels)) {
                continue;
            }
            if (isset($this->subscribedChannels[$channel])) {
                continue;
            }
            $this->nsq->subscribe(
                $this->name,
                $channel,
                function(Message $message) use ($topic, $channel, $consumer) {
                    $consumer->consume($topic, $channel, $message->getPayload());
                }
            );
            $this->subscribedChannels[$channel] = 1;
        }
        $this->nsq->run($timeout);
    }
}
