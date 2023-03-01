<?php
/**
 * @Entity @Table(name="Car")
 **/
class Car {
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @Column(type="string") **/
    protected $existsId;

    public function __construct() {
    }
}