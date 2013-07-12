<?php

namespace Socloz\NsqBundle\Topic;

use nsqphp\Exception\ExpiredMessageException;
use Socloz\NsqBundle\Consumer\ConsumerInterface;

class Stub extends Topic
{
    private $messages = array();

    public function publish($payload, $delay = 0)
    {
        $this->messages[] = array(
            'delay'    => $delay,
            'payload'  => $payload,
            'channels' => array(),
        );
    }

    public function consume(array $channels = array(), $options = null)
    {
        $options = array_merge(
            array(
                'limit' => 1,
                'ignore_delay' => false,
                'filter' => null,
                'consumers' => array()
            ),
            is_array($options) ? $options : array()
        );
        $filter = $options['filter'];
        $count = 0;
        $consumers = array_merge($this->getConsumers(), $options['consumers']);
        foreach ($this->messages as $message) {
            if ($options['limit'] > 0 && $count++ >= $options['limit']) {
                break;
            }
            if (!$options['ignore_delay'] && $message['delay'] > 0) {
                continue;
            }
            if ($filter && !$filter($message['payload'])) {
                continue;
            }
            foreach ($consumers as $channel => $consumer) {
                if (in_array($channel, $message['channels'])) {
                    continue;
                }
                if ($channels && !in_array($channel, $channels)) {
                    continue;
                }
                try {
                    if ($consumer instanceof ConsumerInterface) {
                        $consumer->consume($this->getName(), $channel, $message['payload']);
                    } else {
                        $consumer($message['payload']);
                    }
                } catch (ExpiredMessageException $e) {
                }
                $message['channels'][] = $channel;
            }
        }
    }

    public function clear()
    {
        $this->messages = array();
    }

    public function getMessageDelays($filter)
    {
        if (!is_callable($filter)) {
            throw new \InvalidArgumentException("Invalid filter callback");
        }
        $delays = array();
        foreach ($this->messages as $m) {
            if ($filter($m['payload'])) {
                $delays[] = $m['delay'];
            }
        }
        return $delays;
    }

    public function delete($filter)
    {
        if (!is_callable($filter)) {
            throw new \InvalidArgumentException("Invalid filter callback");
        }
        foreach ($this->messages as $k => $m) {
            if ($filter($m['payload'])) {
                unset($this->messages[$k]);
            }
        }
    }
}
