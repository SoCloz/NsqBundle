# SoclozNsqBundle

This bundle allow your Symfony2 application to work with [NSQ](https://github.com/bitly/nsq) queuing system.
It is a very light wrapper over [NSQPHP](https://github.com/davegardnerisme/nsqphp) (NSQ client library for PHP).

## Installation

### Add the dependency in your composer.json

```js
{
    "require": {
        "socloz/nsq-bundle": "dev-master"
    }
}
```

### Enable the bundle in your application kernel

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Socloz\NsqBundle\SoclozNsqBundle(),
    );
}
```

## Configuration

```yaml
# app/config.yml

socloz_nsq:
  lookupd_hosts: ["localhost:4161"] # list of nsqlookupd hosts, optional. If omitted the consumers
                                    # will use the "publish_to" list (see below) of the topic.
  topics:
    foo:                      # topic name
      publish_to: [localhost] # list of nsqd hosts to publish to
      requeue_strategy:       # requeuing strategy on failure
        max_attempts: 5       # maximum number of attemps per message
        delays:               # requeuing delays in ms
          - 5000
          - 10000
          - 60000
          # 4st (and last) requeue will use the last specified delay (ie 60000)
```

## Specifiying consumers

### Via configuration

```yaml
# app/config.yml

socloz_nsq:
  topics:
    foo:
      consumers:
        channel1: amce_consumer1 # service id
        channel2: amce_consumer2
        # ...
```

### Via service definition

```yaml
# AcmeBundle/Resources/config/services.yml

amce_consumer1:
  class: AcmeBundle\Consumer1
  tags:
    -  { name: socloz.nsq.consumer, topic: foo, channel: acme.channel1 }
```

## Usage

### Producer side

```php
<?php

use nsqphp\Message\Message;

// ...

$topic = $container->get('socloz.nsq')->getTopic('foo');
$topic->publish('message payload');
```

### Consumer side

```php
<?php
// AcmeBundle/Consumer1.php

namespace AcmeBundle;

use nsqphp\Message\Message;
use Socloz\NsqBundle\Consumer\ConsumerInterface;

class Consumer1 implements ConsumerInterface
{
    public function consume($topic, $channel, $payload)
    {
        // ...
    }
}
```

### Run consumers for a topic

```
app/console socloz:nsq:topic:consume <topic> [-c <channel>]
```

### Delayed messages

Delayed messages are supported via an intermediate topic. When you publish a
message with a delay it will be first published to a dedicated "hidden" topic
(named "__socloz_delayed") on a specified host. While this message is not ready
it will be requeued with the remaining delay. Once the message is ready, it will
be eventually published to the business topic.

#### Configuration

Here is the default configuration of the delayed messages's topic.

```yaml
# app/config.yml

socloz_nsq:
  delayed_messages_topic:
    publish_to: localhost # single host
    requeue_strategy:     # 4 retries with 10s interval
      max_attempts: 5
      delays: [10000]
```

#### Publish a message with a delay

```php
<?php
// ...
$topic->publish(
    'message payload',
    600 // will be ready in 10 minutes
);
```

#### Run the consumer for the dedicated topic

```
app/console socloz:nsq:delayed:consume
```

### Misc

Get consumers list for a given topic

```
app/console socloz:nsq:topic:list <topic>
```

# TODO
- Inject logger
