<?php

namespace Socloz\NsqBundle\Delayed;

use Socloz\NsqBundle\Topic\Topic as BaseTopic;

class Topic extends BaseTopic
{
    public function publish($message, $delay = 0)
    {
        if (!$message instanceof Message) {
            throw new \Exception("Invalid delayed message type");
        }
        $this->doPublish($message->toPayload());
    }
}
