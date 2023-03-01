<?php
require_once __DIR__ . "/vendor/autoload.php";

$classDirs = [
    __DIR__ . '/entities',
];

new \iRAP\Autoloader\Autoloader($classDirs);

function getEntityManager() : \Doctrine\ORM\EntityManager
{
    $entityManager = null;

    if ($entityManager === null)
    {
        $paths = array(__DIR__ . '/entities');
        $config = \Doctrine\ORM\ORMSetup::createAttributeMetadataConfiguration($paths);

        # set up configuration parameters for doctrine.
        $dbParams = array(
            'driver'         => 'pdo_pgsql',
            'user'           => 'user1',
            'password'       => 'my-awesome-password',
            'host'           => 'postgresql.mydomain.com',
            'port'           => 5432,
            'dbname'         => 'myDbName',
            'charset'        => 'UTF-8',
        );


        $entityManager = \Doctrine\DBAL\DriverManager::getConnection($dbParams, $config);
    }

    return $entityManager;
}