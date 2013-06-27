<?php

namespace Socloz\NsqBundle\Delayed;

use Socloz\NsqBundle\Topic\Topic as BaseTopic;

class Topic extends BaseTopic
{
    public function publish(Message $message)
    {
        $this->doPublish($message->toPayload());
    }
}
