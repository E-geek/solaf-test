<?php
namespace DB;

require_once __DIR__ . "/../vendor/autoload.php";

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

$config = ORMSetup::createAnnotationMetadataConfiguration(
    [__DIR__ . "/entities"],
     true,
);

$dbParams = array(
    'driver' => 'pdo_pgsql',
    'user' => $_ENV['PG_USER'] ?? 'solaf',
    'password' => $_ENV['PG_PASSWORD'] ?? 'solaf',
    'host' => $_ENV['PG_HOST'] ?? '192.168.1.20',
    'port' => $_ENV['PG_PORT'] ?? 5432,
    'dbname' => $_ENV['PG_DB'] ?? 'solaf',
    'charset' => 'UTF-8',
);

$connection = DriverManager::getConnection($dbParams, $config);

$entityManager = new EntityManager($connection, $config);
class Tool {
    public static function getInstance() :EntityManager {
        global $entityManager;
        return $entityManager;
    }
}
