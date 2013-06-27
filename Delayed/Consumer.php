<?php

namespace Socloz\NsqBundle\Delayed;

use nsqphp\Exception\RequeueMessageException;

use Socloz\NsqBundle\Consumer\ConsumerInterface;
use Socloz\NsqBundle\Topic\Manager as TopicManager;

class Consumer implements ConsumerInterface
{
    /**
     *
     * @var TopicManager
     */
    private $topicManager;

    public function __construct(TopicManager $tm)
    {
        $this->topicManager = $tm;
    }

    public function consume($topic, $channel, $payload)
    {
        $message = Message::factory($payload);
        $remaining = $message->getScheduledAt() - time();
        if ($remaining > 0) {
            throw new RequeueMessageException($remaining);
        }
        $target = $this->topicManager->getTopic($message->getTargetTopic());
        if (!$target) {
            throw new \Exception(sprintf(
                "Unknown target topic '%s' for message '%s'",
                $message->getTargetTopic(),
                $message->getUserPayload()
            ));
        }
        $target->publish($message->getUserPayload());
    }
}
