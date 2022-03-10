<?php

namespace YaLinqo\Tests\Stubs;

// @codeCoverageIgnoreStart

class Temp {
    public $v;

    public function __construct($value) {
        $this->v = $value;
    }

    public function foo($a) {
        return $this->v + $a;
    }

    public static function bar($a) {
        return $a;
    }
}
