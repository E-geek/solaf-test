<?php

namespace Connector;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../tool/Singleton.php";

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Tool\Singleton;

class MQ extends Singleton {

    private AMQPStreamConnection $connection;

    private array $channels;

    public function __construct() {
        parent::__construct();
        $this->connection = new AMQPStreamConnection(
            $_ENV[ 'MQ_HOST' ] ?? 'localhost',
            $_ENV[ 'MQ_PORT' ] ?? 5672,
            $_ENV[ 'MQ_USER' ] ?? 'solaf',
            $_ENV[ 'MQ_PASS' ] ?? 'solaf',
        );
    }

    public function getChannelByQueue(string $queueName) :AMQPChannel {
        if (!isset($this->channels[ $queueName ])) {
            $channel = $this->channels[ $queueName ] = $this->connection->channel();
            $channel->queue_declare($queueName, false, false, false, false);
            $channel->basic_qos(0, 1, false);
        }
        return $this->channels[ $queueName ];
    }

    public function publish(string $queue, $message) {
        $msg = new AMQPMessage(serialize($message));
        $this->getChannelByQueue($queue)->basic_publish($msg, '', $queue);
    }
}

