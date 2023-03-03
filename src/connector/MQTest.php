<?php

namespace Tests;

use Connector\MQ;
use PHPUnit\Framework\TestCase;

class MQTest extends TestCase {

    public function test__construct() {
        $this->assertInstanceOf('Connector\MQ', MQ::getInstance());
    }

    public function testGetChannelByQueue() {
        $channel = MQ::getInstance()->getChannelByQueue('test');
        $this->assertInstanceOf('PhpAmqpLib\Channel\AMQPChannel', $channel);
        $this->assertTrue($channel->is_open());
        $channel->close();
    }
}
