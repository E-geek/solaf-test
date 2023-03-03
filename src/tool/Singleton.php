<?php

namespace Tool;

abstract class Singleton {
    private static $instances = [];

    protected function __construct() {
    }

    public static function getInstance() {
        $cls = static::class;
        if (!isset(self::$instances[ $cls ])) {
            self::$instances[ $cls ] = new static();
        }

        return self::$instances[ $cls ];
    }

    public static function isInit() :bool {
        $cls = static::class;
        return isset(self::$instances[ $cls ]);
    }
}