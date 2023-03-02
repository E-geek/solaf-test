<?php
namespace DB;

require_once __DIR__ . "/vendor/autoload.php";

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

$config = ORMSetup::createAnnotationMetadataConfiguration(
    [__DIR__ . "/entities"],
     true,
);

$dbParams = array(
    'driver' => 'pdo_pgsql',
    'user' => 'solaf',
    'password' => 'solaf',
    'host' => '127.0.0.1',
    'port' => 5432,
    'dbname' => 'solaf',
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
