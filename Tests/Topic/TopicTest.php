<?php

namespace Socloz\NsqBundle\Tests\Topic;

use React\EventLoop\LoopInterface;

use nsqphp\Message\Message;

use Socloz\NsqBundle\Topic\Manager;
use Socloz\NsqBundle\Consumer\ConsumerInterface;
use Socloz\NsqBundle\Delayed\Consumer as DelayedMessagesConsumer;

class TopicTest extends \PHPUnit_Framework_TestCase implements ConsumerInterface
{
    /**
     *
     * @var LoopInterface
     */
    private $loop;

    /**
     *
     * @var array
     */
    private $messages = array();

    private $exception;

    private $rsDelays = array(
        2000,
        3000,
    );

    public function setUp()
    {
        $fp = @fsockopen('localhost', 4150);
        if ($fp) {
            fclose($fp);
        } else {
            $this->markTestSkipped(
                'nsqd is not running'
            );
        }
    }

    public function test()
    {
        $delayedMessagesTopicName = 'test-delayed-' . rand();
        $this->manager = $manager = new Manager($delayedMessagesTopicName);
        $topicName = 'test-' . rand();
        $manager->setDelayedMessagesTopic(array(
            'publish_to' => 'localhost',
            'requeue_strategy' => array(
                'max_attempts' => 10,
                'delays' => array(50)
            )
        ));
        $manager->setTopic($topicName, array(
            'publish_to' => array('localhost'),
            'requeue_strategy' => array(
                'max_attempts' => 3,
                'delays' => $this->rsDelays
            )
        ));
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new \Exception('fork');
        }
        if ($pid == 0) {
            $manager->setTopicConsumer(
                $delayedMessagesTopicName,
                'default',
                new DelayedMessagesConsumer($manager)
            );
            $manager->consumeDelayedMessagesTopic(5);
            exit;
        }
        $manager->setTopicConsumer($topicName, 'default', $this);
        $this->messages = array(
            array(
                'data' => 'data_' . rand(),
                'delay' => 2,
            ),
            array(
                'data' => 'data_' . rand(),
                'delay' => 1,
            ),
            array(
                'data' => 'data_' . rand(),
                'delay' => 0,
                'retries' => 2
            ),
            array(
                'data' => 'data_' . rand(),
                'delay' => 3,
                'retries' => 1
            ),
        );
        foreach ($this->messages as $k => $message) {
            $manager->getTopic($topicName)->publish($message['data'], $message['delay']);
            $this->messages[$k]['ts'] = time();
            $this->messages[$k]['attempts'] = 0;
        }
        $this->loop = $this->readAttribute(
            $this->readAttribute($manager->getTopic($topicName), 'nsq'),
            'loop'
        );
        $manager->getTopic($topicName)->consume(array(), 7);
    }

    public function consume($topic, $channel, $payload)
    {
        if (!count($this->messages)) {
            return;
        }
        try {
            if (!$this->exception) {
                $this->checkMessage($topic, $channel, $payload);
            }
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            $this->exception = $e;
        }
        if (count($this->messages) == 0) {
            $this->loop->stop();
        }
    }

    private function checkMessage($topic, $channel, $payload)
    {
        foreach ($this->messages as $k => $infos) {
            if ($payload != $infos['data']) {
                continue;
            }
            $this->messages[$k]['attempts'] = ++$infos['attempts'];
            if ($infos['attempts'] == 1) {
                if (isset($infos['delay']) && $infos['delay']) {
                    $this->assertLooselyEqualsToNow(
                        $infos['delay'] + $infos['ts']
                    );
                }
            }
            if (isset($infos['retries'])) {
                if ($infos['attempts'] > 1) {
                    $expectedDelay = array_sum(array_slice(
                        $this->rsDelays,
                        0,
                        $infos['attempts'] - 1
                    ));
                    $expectedDelay /= 1000;
                    $this->assertLooselyEqualsToNow(
                        $expectedDelay + $infos['ts'] + $infos['delay']
                    );
                }
                if ($infos['attempts'] - 1 < $infos['retries']) {
                    throw new \Exception();
                }
            } else {
                $this->assertEquals(1, $infos['attempts']);
            }
            unset($this->messages[$k]);
        }
    }

    private function assertLooselyEqualsToNow($ts)
    {
        $time = time();
        $this->assertLessThanOrEqual($time, $ts);
        $this->assertGreaterThanOrEqual($time - 1, $ts);
    }

    public function tearDown()
    {
        if ($this->exception) {
            throw $this->exception;
        }
        $this->assertEmpty($this->messages, var_export($this->messages, true));
    }
}
