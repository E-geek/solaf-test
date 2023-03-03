<?php

namespace Runner;

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Connector\MQ;

class ImageLoader {
    /**
     * @var mixed|string
     */
    private $storage;

    private AMQPChannel $channel;

    public function __construct() {
        $this->storage = $_ENV[ 'APP_STORAGE' ] ?? __DIR__ . '/../storage';
        $this->channel = MQ::getInstance()->getChannelByQueue('s-image-loader');
    }

    protected function _onMessage(AMQPMessage $msg) {
        $message = unserialize($msg->body);
        switch ($message[ 'type' ]) {
            case 'shutdown':
                $this->_shutdown();
                break;
            case 'download':
                list('link' => $link, 'filename' => $filename, 'storage' => $storage) = $message[ 'body' ];
                $this->_toStore($link, $filename, $this->storage);
                break;
            default:
                echo 'Unknown type of message';
        }
    }

    private function _toStore(string $link, string $filename, string $storage) {
        // really? This is lightness way to download pic to FS? Hehe.
        echo "Downloading $link to $filename... ";
        self::forceFilePutContents($this->storage . '/' . $filename, file_get_contents($link));
        echo "Done!" . PHP_EOL;
    }

    public static function forceFilePutContents(string $fullPathWithFileName, string $fileContents) {
        $exploded = explode(DIRECTORY_SEPARATOR, $fullPathWithFileName);
        array_pop($exploded);
        $directoryPathOnly = implode(DIRECTORY_SEPARATOR, $exploded);
        if (!file_exists($directoryPathOnly)) {
            mkdir($directoryPathOnly, 0775, true);
        }
        file_put_contents($fullPathWithFileName, $fileContents);
    }

    private function _shutdown() {
        exit(0);
    }

    public function watch() {
        $onMessage = function (AMQPMessage $msg) {
            $this->_onMessage($msg);
        };
        $this->channel->basic_consume('s-image-loader', '', false, true, false, false, $onMessage);
        echo "Worker up. Awaiting tasks..." . PHP_EOL;
        while ($this->channel->is_open()) {
            try {
                $this->channel->wait();
            } catch (\ErrorException $e) {
                echo "MQ adaptor throw error: " . $e->getMessage();
                exit(1);
            }
        }
    }
}