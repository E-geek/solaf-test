<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/connector/mq.php';

use PhpAmqpLib\Message\AMQPMessage;
use Connector\MQ;

function loader() {
    $storage = $_ENV['APP_STORAGE'] ?? __DIR__ . '/../storage';

    echo "Start slave" . PHP_EOL;
    $channel = MQ::getInstance()->getChannelByQueue('s-image-loader');

    $onMessage = function(AMQPMessage $msg) use ($storage) {
        $message = unserialize($msg->body);
        switch($message['type']) {
            case 'shutdown':
                shutdown();
                break;
            case 'download':
                extract($message['body']); // unsafe move
                toStore($link, $filename, $storage);
                break;
            default:
                echo 'Unknown type of message';
        }
    };

    function forceFilePutContents (string $fullPathWithFileName, string $fileContents) {
        $exploded = explode(DIRECTORY_SEPARATOR,$fullPathWithFileName);
        array_pop($exploded);
        $directoryPathOnly = implode(DIRECTORY_SEPARATOR,$exploded);
        if (!file_exists($directoryPathOnly)) {
            mkdir($directoryPathOnly,0775,true);
        }
        file_put_contents($fullPathWithFileName, $fileContents);
    }

    function toStore(string $link, string $filename, string $storage) {
        // really? This is lightness way to download pic to FS? Hehe.
        echo "Downloading $link to $filename... ";
        forceFilePutContents($storage . '/' . $filename, file_get_contents($link));
        echo "Done!" . PHP_EOL;
    };

    function shutdown() {
        exit(0);
    };

    $channel->basic_consume('s-image-loader', '', false, true, false, false, $onMessage);

    echo "Awaiting..." . PHP_EOL;
    while ($channel->is_open()) {
        $channel->wait();
    }
}

loader();