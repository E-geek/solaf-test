<?php

namespace Entity;

use PHPUnit\Framework\TestCase;

class CarTest extends TestCase {

    public function test__emptyConstruct() {
        $car = new Car();
        $this->assertInstanceOf('Entity\Car', $car);
        $this->assertNull($car->existsId);
    }

    public function test__construct() {
        $car = new Car([
            'existsId' => 'test--exists-id',
            'title' => 'test-title',
            'extra1' => 'test-extra1',
            'meta1' => 'test-meta1',
        ]);
        $this->assertEquals('test--exists-id', $car->existsId);
        $this->assertEquals('test-title', $car->title);
        $this->assertEquals('test-extra1', $car->extra1);
        $this->assertEquals('test-meta1', $car->meta1);
        $expected = [
            'extra1' => 'test-extra1',
            'meta1' => 'test-meta1',
        ];
        $actual = $car->extra;
        $this->assertEmpty(
            array_merge(array_diff($expected, $actual), array_diff($actual, $expected)),
            'Data stored in extra when property does not exists');
    }
}
