<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mq.php';

use PhpAmqpLib\Message\AMQPMessage;
use React\EventLoop\Loop;
use React\ChildProcess\Process;

function observe() {
    global $mqConnection;
    $externalChannel = $mqConnection->channel();
    $internalChannel = $mqConnection->channel();
    echo "Connect to RabbitMQ" . PHP_EOL;
    $externalChannel->queue_declare('image-link', false, false, false, false);
    $internalChannel->queue_declare('s-image-loader', false, false, false, false);
    $externalChannel->basic_qos(null, 1, null);
    $loop = Loop::get();

    $onMessage = function(AMQPMessage $msg) use ($internalChannel) {
        $body = unserialize($msg->body);
        echo "Receive " . $body['link'] . PHP_EOL;
        if ($body['type'] === 'system' && $body['body'] === 'done') {
            onEnd($internalChannel);
        }
        $task = new AMQPMessage(serialize([
            'type' => 'download',
            'body' => [
                'link' => $body['link'],
                'filename' => $body['filename'],
            ],
        ]));
        $internalChannel->basic_publish($task, '', 's-image-loader');
    };

    $externalChannel->basic_consume('image-link', '', false, true, false, false, $onMessage);

    function onEnd($internalChannel) {
        echo 'Aggregator sent "done"';
        $task = new AMQPMessage([
            'type' => 'shutdown',
        ]);
        $internalChannel->basic_publish($task, '', 's-image-loader');
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