<?php

namespace Socloz\NsqBundle\Consumer;

interface ConsumerInterface
{
    public function consume($topic, $channel, $payload);
}
