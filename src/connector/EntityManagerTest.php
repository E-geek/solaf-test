<?php

namespace Tests;

use Connector\EntityManager;
use PHPUnit\Framework\TestCase;

class EntityManagerTest extends TestCase {

    public function testGet() {
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', EntityManager::get(), '::get() returns original ORM\\EntityManager');
    }

    public function testClose() {
        $this->assertTrue(EntityManager::isConstructed());
        EntityManager::close();
        $this->assertTrue(EntityManager::isConstructed());
        $this->assertFalse(EntityManager::get()->isOpen());
    }

    public function test__construct() {
        $this->assertIsArray(EntityManager::get()
            ->getConnection()
            ->prepare('SELECT now();')
            ->executeQuery()
            ->fetchAllAssociative()
        );
    }
}
