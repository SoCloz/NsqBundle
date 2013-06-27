<?php

namespace Socloz\NsqBundle\Delayed;

class Message
{
    /**
     *
     * @var string
     */
    private $targetTopic;

    /**
     *
     * @var string
     */
    private $userPayload;

    /**
     *
     * @var delay
     */
    private $scheduledAt;

    public function __construct($targetTopic, $userPayload, $delay)
    {
        $this->targetTopic = $targetTopic;
        $this->userPayload = $userPayload;
        $this->scheduledAt = time() + (int) $delay;
    }

    public function getTargetTopic()
    {
        return $this->targetTopic;
    }

    public function getUserPayload()
    {
        return $this->userPayload;
    }

    public function getScheduledAt()
    {
        return $this->scheduledAt;
    }

    public function toPayload()
    {
        return json_encode(get_object_vars($this));
    }

    /**
     *
     * @param string $payload
     * @return Message
     */
    public static function factory($payload)
    {
        $data = json_decode($payload, true);
        $m = new self('', '', '');
        foreach ($data as $k => $v) {
            $m->$k = $v;
        }
        return $m;
    }
}
