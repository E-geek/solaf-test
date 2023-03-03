<?php

namespace Runner;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../connector/mq.php';

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use React\ChildProcess\Process;
use React\EventLoop\Loop;
use Connector\MQ;

class ImageLoaderObserver {
    private AMQPChannel $externalChannel;

    private AMQPChannel $internalChannel;

    private \React\EventLoop\LoopInterface $loop;

    private Process $process;

    public function __construct() {
        $this->externalChannel = MQ::getInstance()->getChannelByQueue('image-link');
        $this->internalChannel = MQ::getInstance()->getChannelByQueue('s-image-loader');
        echo "Connect to RabbitMQ" . PHP_EOL;
        $this->loop = Loop::get();
    }

    private function _onMessage(AMQPMessage $msg) {
        $body = unserialize($msg->body);
        echo "Receive " . $body[ 'link' ] . PHP_EOL;
        if ($body[ 'type' ] === 'system' && $body[ 'body' ] === 'done') {
            $this->_onEnd();
        }
        MQ::getInstance()->publish('s-image-loader', [
            'type' => 'download',
            'body' => [
                'link' => $body[ 'link' ],
                'filename' => $body[ 'filename' ],
            ],
        ]);
    }

    private function _onEnd() {
        echo 'Aggregator sent "done"';
        MQ::getInstance()->publish('s-image-loader', [
            'type' => 'shutdown',
        ]);
    }

    private function _watchMQ() {
        $onMessage = function (AMQPMessage $msg) {
            $this->_onMessage($msg);
        };
        $this->externalChannel->basic_consume('image-link', '', false, true, false, false, $onMessage);
    }

    private function _initWorkers(string $cmdWorker) {
        $this->process = new Process($cmdWorker);
        $this->process->start($this->loop);
        $this->process->on('exit', function ($exitCode) {
            echo 'Process exited with code ' . $exitCode . PHP_EOL;
            $this->externalChannel->close();
            $this->internalChannel->close();
            exit(0);
        });
        $this->process->stdout->on('data', function ($chunk) {
            echo $chunk;
        });
    }

    private function _loop() {
        $this->loop->addPeriodicTimer(0.01, function () {
            if ($this->externalChannel->is_open()) {
                $this->externalChannel->wait(null, true);
            }
        });
        $this->loop->run();
    }

    public function watch(string $cmdWorker) {
        $this->_watchMQ();
        $this->_initWorkers($cmdWorker);
        echo "Worker await tasks" . PHP_EOL;
        $this->_loop();
    }
}
