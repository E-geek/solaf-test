<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/connector/mq.php';

use PhpAmqpLib\Message\AMQPMessage;
use React\EventLoop\Loop;
use React\ChildProcess\Process;
use Connector\MQ;

function observe() {
    $externalChannel = MQ::getInstance()->getChannelByQueue('image-link');
    $internalChannel = MQ::getInstance()->getChannelByQueue('s-image-loader');
    echo "Connect to RabbitMQ" . PHP_EOL;
    $loop = Loop::get();

    $onMessage = function(AMQPMessage $msg) use ($internalChannel) {
        $body = unserialize($msg->body);
        echo "Receive " . $body['link'] . PHP_EOL;
        if ($body['type'] === 'system' && $body['body'] === 'done') {
            onEnd($internalChannel);
        }
        MQ::getInstance()->publish('s-image-loader', [
            'type' => 'download',
            'body' => [
                'link' => $body['link'],
                'filename' => $body['filename'],
            ],
        ]);
    };

    $externalChannel->basic_consume('image-link', '', false, true, false, false, $onMessage);

    function onEnd($internalChannel) {
        echo 'Aggregator sent "done"';
        MQ::getInstance()->publish('s-image-loader', [
            'type' => 'shutdown',
        ]);
    }

    echo "Run workers" . PHP_EOL;
    $process = new Process('php ' . __DIR__ . '/image-loader.php');
    $process->start($loop);
    $process->on('exit', function($exitCode) {
        global $externalChannel, $internalChannel;
        echo 'Process exited with code ' . $exitCode . PHP_EOL;
        $externalChannel->close();
        $internalChannel->close();
        exit(0);
    });
    $process->stdout->on('data', function($chunk) {
        echo $chunk;
    });
    echo "Worker await tasks! (amazing)" . PHP_EOL;
    $loop->addPeriodicTimer(0.01, function() use ($externalChannel) {
        if ($externalChannel->is_open()) {
            $externalChannel->wait(null, true);
        }
    });
    $loop->run();
}

observe();