<?php

namespace Connector;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../tool/Singleton.php";

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM;
use Doctrine\ORM\ORMSetup;
use Tool\Singleton;

class EntityManager extends Singleton {
    private ORM\EntityManager $entityManager;

    private \Doctrine\DBAL\Connection $connection;

    public function __construct() {
        parent::__construct();
        $config = ORMSetup::createAnnotationMetadataConfiguration(
            [__DIR__ . "/../entities"],
            true,
        );

        $dbParams = array(
            'driver' => 'pdo_pgsql',
            'user' => $_ENV[ 'PG_USER' ] ?? 'solaf',
            'password' => $_ENV[ 'PG_PASSWORD' ] ?? 'solaf',
            'host' => $_ENV[ 'PG_HOST' ] ?? '127.0.0.1',
            'port' => $_ENV[ 'PG_PORT' ] ?? 8432,
            'dbname' => $_ENV[ 'PG_DB' ] ?? 'solaf',
            'charset' => 'UTF-8',
        );

        $this->connection = DriverManager::getConnection($dbParams, $config);

        $this->entityManager = new ORM\EntityManager($this->connection, $config);
    }

    public static function get() :ORM\EntityManager {
        return self::getInstance()->entityManager;
    }

    public static function close() {
        if (!self::isInit() || !self::getInstance()->entityManager->isOpen()) {
            return;
        }
        self::getInstance()->entityManager->close();
        self::getInstance()->connection->close();
    }
}
