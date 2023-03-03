<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$mqConnection = new AMQPStreamConnection(
    $_ENV['MQ_HOST'] ?? 'localhost',
    $_ENV['MQ_PORT'] ?? 5672,
    $_ENV['MQ_USER'] ?? 'solaf',
    $_ENV['MQ_PASS'] ?? 'solaf',
);