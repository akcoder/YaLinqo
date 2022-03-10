<?php

namespace YaLinqo\Tests\Unit;

use YaLinqo\Functions as F;
use YaLinqo\Tests\Testing\TestCaseEnumerable;

/** @covers \YaLinqo\Functions
 */
class FunctionsTest extends TestCaseEnumerable
{
    /** @covers \YaLinqo\Functions::init
     */
    public function testInit(): void {
        F::init();
        $this->assertNotEmpty(F::$identity);
    }

    public function testIdentity(): void {
        $f = F::$identity;
        $this->assertSame(2, $f(2));
    }

    public function testKey(): void {
        $f = F::$key;
        $this->assertSame(3, $f(2, 3));
    }

    public function testValue(): void {
        $f = F::$value;
        $this->assertSame(2, $f(2, 3));
    }

    public function testTrue(): void {
        $f = F::$true;
        $this->assertTrue($f());
    }

    public function testFalse(): void {
        $f = F::$false;
        $this->assertFalse($f());
    }

    public function testBlank(): void {
        $f = F::$blank;
        $this->assertNull($f());
    }

    public function testCompareStrict(): void {
        $f = F::$compareStrict;
        $this->assertSame(-1, $f(2, 3));
        $this->assertSame(-1, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(1, $f(3, 2));
    }

    public function testCompareStrictReversed(): void {
        $f = F::$compareStrictReversed;
        $this->assertSame(1, $f(2, 3));
        $this->assertSame(1, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(-1, $f(3, 2));
    }

    public function testCompareLoose(): void {
        $f = F::$compareLoose;
        $this->assertSame(-1, $f(2, 3));
        $this->assertSame(0, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(1, $f(3, 2));
    }

    public function testCompareLooseReversed(): void {
        $f = F::$compareLooseReversed;
        $this->assertSame(1, $f(2, 3));
        $this->assertSame(0, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(-1, $f(3, 2));
    }

    public function testCompareInt(): void {
        $f = F::$compareInt;
        $this->assertSame(-1, $f(2, 3));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(1, $f(3, 2));
    }

    public function testCompareIntReversed(): void {
        $f = F::$compareIntReversed;
        $this->assertSame(1, $f(2, 3));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(-1, $f(3, 2));
    }

    public function testIncrement(): void {
        $f = F::increment();
        $this->assertSame(0, $f());
        $this->assertSame(1, $f());
        $this->assertSame(2, $f());

        $g = F::increment();
        $this->assertSame(0, $g());
        $this->assertSame(1, $g());
        $this->assertSame(3, $f());
    }
}
