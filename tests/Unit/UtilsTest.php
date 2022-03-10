<?php

namespace YaLinqo\Tests\Unit;

use InvalidArgumentException;
use RuntimeException;
use TypeError;
use YaLinqo\Functions as F;
use YaLinqo\Tests\Stubs\Temp;
use YaLinqo\Tests\Testing\TestCaseEnumerable;
use YaLinqo\Utils as U;

/**
 * @internal
 * @coversNothing
 */
final class UtilsTest extends TestCaseEnumerable {
    /** @covers \YaLinqo\Utils::createLambda
     */
    public function testCreateLambdaNullWithoutDefault(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(U::ERROR_CLOSURE_NULL);
        U::createLambda(null);
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    public function testCreateLambdaWithString(): void {
        $this->expectException(TypeError::class);
        U::createLambda('function does not exist', 'a,b');
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    public function testCreateLambdaNullWithDefault(): void {
        $f = U::createLambda(null, true);
        static::assertTrue($f);
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    public function testCreateLambdaClosure(): void {
        $f = U::createLambda(static fn($a, $b) => $a + $b);
        static::assertSame(5, $f(2, 3));
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    public function testCreateLambdaCallableStringThrowsRuntimeException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(U::ERROR_CLOSURE_MOST_NOT_BE_STRING);
        U::createLambda('strlen');
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    public function testCreateLambdaCallableArray(): void {
        $object = new Temp(2);
        $f = U::createLambda([$object, 'foo']);
        static::assertSame(5, $f(3));

        $f = U::createLambda([Temp::class, 'bar']);
        static::assertSame(4, $f(4));

        $f = U::createLambda([get_class($object), 'bar']);
        static::assertSame(6, $f(6));
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    public function testCreateComparerDefault(): void {
        $isReversed = null;
        $f = U::createComparer(null, SORT_ASC, $isReversed);
        static::assertSame(F::$compareStrict, $f);
        static::assertFalse($isReversed);

        $isReversed = null;
        $f = U::createComparer(null, SORT_DESC, $isReversed);
        static::assertSame(F::$compareStrictReversed, $f);
        static::assertFalse($isReversed);
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    public function testCreateComparerSortFlags(): void {
        $isReversed = null;

        $f = U::createComparer(SORT_REGULAR, SORT_ASC, $isReversed);
        static::assertSame(F::$compareStrict, $f);

        $f = U::createComparer(SORT_STRING, SORT_ASC, $isReversed);
        static::assertSame('strcmp', $f);

        $f = U::createComparer(SORT_STRING | SORT_FLAG_CASE, SORT_ASC, $isReversed);
        static::assertSame('strcasecmp', $f);

        $f = U::createComparer(SORT_LOCALE_STRING, SORT_ASC, $isReversed);
        static::assertSame('strcoll', $f);

        $f = U::createComparer(SORT_NATURAL, SORT_ASC, $isReversed);
        static::assertSame('strnatcmp', $f);

        $f = U::createComparer(SORT_NATURAL | SORT_FLAG_CASE, SORT_ASC, $isReversed);
        static::assertSame('strnatcasecmp', $f);
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    public function testCreateComparerSortFlagsNumeric(): void {
        $isReversed = null;
        $f = U::createComparer(SORT_NUMERIC, SORT_ASC, $isReversed);
        static::assertSame(F::$compareInt, $f);
        static::assertFalse($isReversed);

        $isReversed = null;
        $f = U::createComparer(SORT_NUMERIC, SORT_DESC, $isReversed);
        static::assertSame(F::$compareIntReversed, $f);
        static::assertFalse($isReversed);
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    public function testCreateComparerSortFlagsClosure(): void {
        $isReversed = null;
        $f = U::createComparer(static fn($a, $b) => $a-$b, SORT_ASC, $isReversed);
        static::assertSame(7, $f(10, 3));
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    public function testCreateComparerSortFlagsInvalid(): void {
        $this->expectException(InvalidArgumentException::class);
        $isReversed = null;
        U::createComparer(666, SORT_ASC, $isReversed);
    }

    /** @covers \YaLinqo\Utils::lambdaToSortFlagsAndOrder
     */
    public function testLambdaToSortFlagsAndOrderSortFlags(): void {
        $order = SORT_ASC;
        static::assertNull(U::lambdaToSortFlagsAndOrder('$v', $order));

        $order = SORT_ASC;
        static::assertSame(SORT_REGULAR, U::lambdaToSortFlagsAndOrder(null, $order));

        $order = SORT_ASC;
        static::assertSame(SORT_STRING, U::lambdaToSortFlagsAndOrder('strcmp', $order));

        $order = SORT_ASC;
        static::assertSame(SORT_STRING | SORT_FLAG_CASE, U::lambdaToSortFlagsAndOrder('strcasecmp', $order));

        $order = SORT_ASC;
        static::assertSame(SORT_LOCALE_STRING, U::lambdaToSortFlagsAndOrder('strcoll', $order));

        $order = SORT_ASC;
        static::assertSame(SORT_NATURAL, U::lambdaToSortFlagsAndOrder('strnatcmp', $order));

        $order = SORT_ASC;
        static::assertSame(SORT_NATURAL | SORT_FLAG_CASE, U::lambdaToSortFlagsAndOrder('strnatcasecmp', $order));
    }

    /** @covers \YaLinqo\Utils::lambdaToSortFlagsAndOrder
     */
    public function testLambdaToSortFlagsAndOrderSortOrder(): void {
        $order = false;
        U::lambdaToSortFlagsAndOrder(null, $order);
        static::assertSame(SORT_ASC, $order);

        $order = true;
        U::lambdaToSortFlagsAndOrder(null, $order);
        static::assertSame(SORT_DESC, $order);

        $order = SORT_ASC;
        U::lambdaToSortFlagsAndOrder(null, $order);
        static::assertSame(SORT_ASC, $order);

        $order = SORT_DESC;
        U::lambdaToSortFlagsAndOrder(null, $order);
        static::assertSame(SORT_DESC, $order);

        static::assertSame(1, U::lambdaToSortFlagsAndOrder(1, $order));
    }
}
