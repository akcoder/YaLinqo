<?php

namespace YaLinqo\Tests\Unit;

use ArrayIterator;
use EmptyIterator;
use Exception;
use InvalidArgumentException;
use SimpleXMLElement;
use stdClass;
use UnexpectedValueException;
use YaLinqo\Enumerable as E;
use YaLinqo\Errors;
use YaLinqo\Functions;
use YaLinqo\Tests\Stubs\AggregateIteratorWrapper;
use YaLinqo\Tests\Testing\TestCaseEnumerable;

/**
 * @covers \YaLinqo\Enumerable
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class EnumerableTest extends TestCaseEnumerable {
    // region Generation

    /** @covers \YaLinqo\Enumerable::cycle */
    public function testCycle(): void {
        self::assertEnumEquals(
            [ 1, 1, 1 ],
            E::cycle([ 1 ]),
            3);
        self::assertEnumEquals(
            [ 1, 2, 3, 1, 2 ],
            E::cycle([ 1, 2, 3 ]),
            5);
        self::assertEnumEquals(
            [ 1, 2, 1, 2 ],
            E::cycle([ 'a' => 1, 'b' => 2 ]),
            4);
    }

    /** @covers \YaLinqo\Enumerable::cycle */
    public function testCycle_emptySource(): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_ELEMENTS);
        E::cycle([])->toArray();
    }

    /**
     * @covers \YaLinqo\Enumerable::emptyEnum
     * @covers \YaLinqo\Enumerable::__construct
     * @covers \YaLinqo\Enumerable::getIterator
     */
    public function testEmptyEnum(): void {
        self::assertEnumEquals(
            [],
            E::emptyEnum());
    }

    /** @covers \YaLinqo\Enumerable::from */
    public function testFrom_array(): void {
        // from (array)
        self::assertEnumEquals(
            [],
            E::from([]));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ]));
        self::assertEnumEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ]));
        self::assertEnumEquals(
            [ 1, 'a' => 2, '3', true ],
            E::from([ 1, 'a' => 2, '3', true ]));

        // iterators must be ArrayIterators
        self::assertInstanceOf(ArrayIterator::class,
            E::from([ 1, 2, 3 ])->getIterator());
        self::assertInstanceOf(ArrayIterator::class,
            E::from(E::from([ 1, 2, 3 ]))->getIterator());
    }

    /** @covers \YaLinqo\Enumerable::from */
    public function testFrom_enumerable(): void {
        // from (Enumerable)
        self::assertEnumEquals(
            [],
            E::from(E::emptyEnum()));
        self::assertEnumEquals(
            [ 1, 2 ],
            E::from(E::cycle([ 1, 2 ])),
            2);
    }

    /** @covers \YaLinqo\Enumerable::from */
    public function testFrom_iterator(): void {
        // from (Iterator)
        self::assertEnumEquals(
            [],
            E::from(new EmptyIterator()));
        self::assertEnumEquals(
            [ 1, 2 ],
            E::from(new ArrayIterator([1, 2 ])));

        // iterators must be the iterators passed
        self::assertSame(
            $i = new EmptyIterator(),
            E::from($i)->getIterator());
        self::assertSame(
            $i = new ArrayIterator([1, 2 ]),
            E::from($i)->getIterator());
    }

    /** @covers \YaLinqo\Enumerable::from */
    public function testFrom_iteratorAggregate(): void {
        // from (IteratorAggregate)
        self::assertEnumEquals(
            [],
            E::from(new AggregateIteratorWrapper(new EmptyIterator())));
        self::assertEnumEquals(
            [ 1, 2 ],
            E::from(new AggregateIteratorWrapper(new ArrayIterator([1, 2 ]))));

        // iterators must be the iterators passed
        self::assertSame(
            $i = new EmptyIterator,
            E::from(new AggregateIteratorWrapper($i))->getIterator());
        self::assertSame(
            $i = new ArrayIterator([1, 2 ]),
            E::from(new AggregateIteratorWrapper($i))->getIterator());
    }

    /** @covers \YaLinqo\Enumerable::from */
    public function testFrom_SimpleXMLElement(): void {
        // from (SimpleXMLElement)
        self::assertEnumEquals(
            [],
            E::from(new SimpleXMLElement('<r></r>')));
        self::assertEnumValuesEquals(
            [ 'h', 'h', 'g' ],
            E::from(new SimpleXMLElement('<r><h/><h/><g/></r>'))->select(static fn($v, $k) => $k));
    }

    /**
     * @covers \YaLinqo\Enumerable::from
     * @dataProvider dataProvider_testFrom_wrongTypes
     */
    public function testFrom_wrongTypes(mixed $source): void {
        // from (unsupported type)
        $this->setExpectedException(InvalidArgumentException::class);
        E::from($source)->getIterator();
    }

    /** @covers \YaLinqo\Enumerable::from */
    public function dataProvider_testFrom_wrongTypes(): array {
        return [
            [ 1 ],
            [ 2.0 ],
            [ '3' ],
            [ true ],
            [ null ],
            [static function() { } ],
            [ new stdClass() ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::generate */
    public function testGenerate(): void {
        // generate (funcValue)
        self::assertEnumEquals(
            [ false, false, false, false ],
            E::generate(static fn(?int $v) => $v > 0),
            4);
        self::assertEnumEquals(
            [ 2, 4, 6, 8 ],
            E::generate(static fn($v) => $v + 2),
            4);

        // generate (funcValue, seedValue)
        self::assertEnumEquals(
            [ 0, 2, 4, 6 ],
            E::generate(static fn($v) => $v + 2, 0),
            4);
        self::assertEnumEquals(
            [ 1, 2, 4, 8 ],
            E::generate(static fn($v) => $v * 2, 1),
            4);

        // generate (funcValue, seedValue, funcKey, seedKey)
        self::assertEnumEquals(
            [ 1, 3, 5, 7 ],
            E::generate(static fn($k) => $k + 2, 1, null, 0),
            4);
        self::assertEnumEquals(
            [ 3 => 2, 6 => 4, 9 => 6 ],
            E::generate(static fn($v, $k) => $v + 2, null, static fn($v, $k) => $k + 3, null),
            3);
        self::assertEnumEquals(
            [ 2 => 1, 5 => 3, 8 => 5 ],
            E::generate(static fn($v, $k) => $v+2, 1, static fn($v, $k) => $k+3, 2),
            3);
    }

    /** @covers \YaLinqo\Enumerable::generate */
    public function testGenerate_meaningful(): void {
        // Partial sums
        self::assertEnumEquals(
            [ 0, 1, 3, 6, 10, 15 ],
            E::generate(static fn($k, $v) => $k+$v, 0, null, 0)->skip(1)->toValues(),
            6);
        // Fibonacci
        self::assertEnumEquals(
            [ 1, 1, 2, 3, 5, 8 ],
            E::generate(static fn($v, $k) => [ $v[1], $v[0]+$v[1] ], [ 0, 1 ])->select(static fn($v, $k) => $v[1]),
            6);
        // Fibonacci
        self::assertEnumEquals(
            [ 1, 1, 2, 3, 5, 8 ],
            E::generate(static fn($v, $k) =>$k + $v, 1, static fn($v, $k) => $v, 1)->toKeys(),
            6);
    }

    /** @covers \YaLinqo\Enumerable::toInfinity */
    public function testToInfinity(): void {
        // toInfinity ()
        self::assertEnumEquals(
            [ 0, 1, 2, 3 ],
            E::toInfinity(),
            4);

        // toInfinity (start)
        self::assertEnumEquals(
            [ 3, 4, 5, 6 ],
            E::toInfinity(3),
            4);

        // toInfinity (start, step)
        self::assertEnumEquals(
            [ 3, 5, 7, 9 ],
            E::toInfinity(3, 2),
            4);
        self::assertEnumEquals(
            [ 3, 1, -1, -3 ],
            E::toInfinity(3, -2),
            4);
    }

    /** @covers \YaLinqo\Enumerable::matches */
    public function testMatches(): void {
        // without matches
        self::assertEnumEquals(
            [],
            E::matches('abc def', '#\d+#'));
        // with matches, without groups
        self::assertEnumEquals(
            [ [ '123' ], [ '22' ] ],
            E::matches('a123 22', '#\d+#'));
        // with matches, with groups
        self::assertEnumEquals(
            [ [ '123', '1' ], [ '22', '2' ] ],
            E::matches('a123 22', '#(\d)\d*#'));
        // with matches, with groups, pattern order
        self::assertEnumEquals(
            [ [ '123', '22' ], [ '1', '2' ] ],
            E::matches('a123 22', '#(\d)\d*#', PREG_PATTERN_ORDER));
    }

    /** @covers \YaLinqo\Enumerable::toNegativeInfinity */
    public function testToNegativeInfinity(): void {
        // toNegativeInfinity ()
        self::assertEnumEquals(
            [ 0, -1, -2, -3 ],
            E::toNegativeInfinity(),
            4);

        // toNegativeInfinity (start)
        self::assertEnumEquals(
            [ -3, -4, -5, -6 ],
            E::toNegativeInfinity(-3),
            4);

        // toNegativeInfinity (start, step)
        self::assertEnumEquals(
            [ -3, -5, -7, -9 ],
            E::toNegativeInfinity(-3, 2),
            4);
        self::assertEnumEquals(
            [ -3, -1, 1, 3 ],
            E::toNegativeInfinity(-3, -2),
            4);
    }

    /** @covers \YaLinqo\Enumerable::returnEnum */
    public function testReturnEnum(): void {
        self::assertEnumEquals(
            [ 1 ],
            E::returnEnum(1));
        self::assertEnumEquals(
            [ true ],
            E::returnEnum(true));
        self::assertEnumEquals(
            [ null ],
            E::returnEnum(null));
    }

    /** @covers \YaLinqo\Enumerable::range */
    public function testRange(): void {
        // range (start, count)
        self::assertEnumEquals(
            [],
            E::range(3, 0));
        self::assertEnumEquals(
            [],
            E::range(3, -1));
        self::assertEnumEquals(
            [ 3, 4, 5, 6 ],
            E::range(3, 4));

        // range (start, count, step)
        self::assertEnumEquals(
            [ 3, 5, 7, 9 ],
            E::range(3, 4, 2));
        self::assertEnumEquals(
            [ 3, 1, -1, -3 ],
            E::range(3, 4, -2));
    }

    /** @covers \YaLinqo\Enumerable::rangeDown */
    public function testRangeDown(): void {
        // rangeDown (start, count)
        self::assertEnumEquals(
            [],
            E::rangeDown(-3, 0));
        self::assertEnumEquals(
            [],
            E::rangeDown(-3, -1));
        self::assertEnumEquals(
            [ -3, -4, -5, -6 ],
            E::rangeDown(-3, 4));

        // rangeDown (start, count, step)
        self::assertEnumEquals(
            [ -3, -5, -7, -9 ],
            E::rangeDown(-3, 4, 2));
        self::assertEnumEquals(
            [ -3, -1, 1, 3 ],
            E::rangeDown(-3, 4, -2));
    }

    /** @covers \YaLinqo\Enumerable::rangeTo */
    public function testRangeTo(): void {
        // rangeTo (start, end)
        self::assertEnumEquals(
            [],
            E::rangeTo(3, 3));
        self::assertEnumEquals(
            [ 3, 4, 5, 6 ],
            E::rangeTo(3, 7));

        // rangeTo (start, end, step)
        self::assertEnumEquals(
            [ 3, 5, 7, 9 ],
            E::rangeTo(3, 10, 2));
        self::assertEnumEquals(
            [ -3, -4, -5, -6 ],
            E::rangeTo(-3, -7));
        self::assertEnumEquals(
            [ -3, -5, -7, -9 ],
            E::rangeTo(-3, -10, 2));
    }

    /** @covers \YaLinqo\Enumerable::rangeTo */
    public function testRangeTo_zeroStep(): void {
        $this->setExpectedException(InvalidArgumentException::class, Errors::STEP_NEGATIVE);
        E::rangeTo(3, 7, 0);
    }

    /** @covers \YaLinqo\Enumerable::rangeTo */
    public function testRangeTo_negativeStep(): void {
        $this->setExpectedException(InvalidArgumentException::class, Errors::STEP_NEGATIVE);
        E::rangeTo(3, 7, -1);
    }

    /** @covers \YaLinqo\Enumerable::repeat */
    public function testRepeat(): void {
        // repeat (element)
        self::assertEnumEquals(
            [ 3, 3, 3, 3 ],
            E::repeat(3),
            4);

        // repeat (element, count)
        self::assertEnumEquals(
            [ 3, 3, 3, 3 ],
            E::repeat(3, 4));
        self::assertEnumEquals(
            [ true, true ],
            E::repeat(true, 2));
        self::assertEnumEquals(
            [],
            E::repeat(3, 0));
    }

    /** @covers \YaLinqo\Enumerable::repeat
     */
    public function testRepeat_negativeCount(): void {
        $this->setExpectedException(InvalidArgumentException::class, Errors::COUNT_LESS_THAN_ZERO);
        E::repeat(3, -2);
    }

    /** @covers \YaLinqo\Enumerable::split */
    public function testSplit(): void {
        // without empty
        self::assertEnumEquals(
            [ '123 4 44' ],
            E::split('123 4 44', '#, ?#'));
        // with empty
        self::assertEnumEquals(
            [ '123', '4', '44', '' ],
            E::split('123,4, 44,', '#, ?#'));
        // with empty, empty skipped
        self::assertEnumEquals(
            [ '123', '4', '44' ],
            E::split('123,4, 44,', '#, ?#', PREG_SPLIT_NO_EMPTY));
        // with empty, empty skipped, no results
        self::assertEnumEquals(
            [],
            E::split(',', '#, ?#', PREG_SPLIT_NO_EMPTY));
    }

    // endregion

    // region Projection and filtering

    /** @covers \YaLinqo\Enumerable::cast */
    public function testCast(): void {
        $c = new stdClass();
        $c->c = 'd';
        $o = new stdClass();
        $e = new Exception();
        $v = static function($v) {
            $r = new stdClass();
            $r->scalar = $v;
            return $r;
        };

        // cast (empty)
        self::assertEnumValuesEquals([], E::from([])->cast('array'));

        // cast (array)
        $sourceArrays = [null, 1, 1.2, '1.3', 'abc', true, false, [], [1 => 2], ['a' => 'b'], $c];
        $expectedArrays = [[], [1], [1.2], ['1.3'], ['abc'], [true], [ false ], [], [1 => 2], ['a' => 'b'], ['c' => 'd']];
        self::assertEnumValuesEquals($expectedArrays, from($sourceArrays)->cast('array'));

        // cast (int)
        $sourceInts = [null, 1, 1.2, '1.3', 'abc', true, false, [], [1 => 2], ['a' => 'b']];
        $expectedInts = [0, 1, 1, 1, 0, 1, 0, 0, 1, 1];
        self::assertEnumValuesEquals($expectedInts, from($sourceInts)->cast('int'));
        self::assertEnumValuesEquals($expectedInts, from($sourceInts)->cast('integer'));
        self::assertEnumValuesEquals($expectedInts, from($sourceInts)->cast('long'));

        // cast (float)
        $sourceFloats = [null, 1, 1.2, '1.3', 'abc', true, false, [], [ 1 => 2 ], ['a' => 'b']];
        $expectedFloats = [ 0.0, 1.0, 1.2, 1.3, 0.0, 1.0, 0.0, 0.0, 1.0, 1.0 ];
        self::assertEnumValuesEquals($expectedFloats, from($sourceFloats)->cast('float'));
        self::assertEnumValuesEquals($expectedFloats, from($sourceFloats)->cast('real'));
        self::assertEnumValuesEquals($expectedFloats, from($sourceFloats)->cast('double'));

        // cast (null)
        $sourceNulls = [ null, 1, 1.2, '1.3', 'abc', true, false, [], [ 1 => 2 ], [ 'a' => 'b' ], $c, $e ];
        $expectedNulls = [ null, null, null, null, null, null, null, null, null, null, null, null ];
        self::assertEnumValuesEquals($expectedNulls, from($sourceNulls)->cast('null'));
        self::assertEnumValuesEquals($expectedNulls, from($sourceNulls)->cast('unset'));

        // cast (null)
        $sourceNulls = [null, 1, 1.2, '1.3', 'abc', true, false, [], [1 => 2], ['a' => 'b'], $c, $e];
        $expectedNulls = [null, null, null, null, null, null, null, null, null, null, null, null];
        self::assertEnumValuesEquals($expectedNulls, from($sourceNulls)->cast('null'));
        self::assertEnumValuesEquals($expectedNulls, from($sourceNulls)->cast('unset'));

        // cast (object)
        $sourceObjects = [null, 1, 1.2, '1.3', 'abc', true, false, [], [1 => 2], ['a' => 'b'], $c, $e];
        $expectedObjects = [$o, $v(1), $v(1.2), $v('1.3'), $v('abc'), $v(true), $v(false), $o, (object)[ 1 => 2 ], (object)['a' => 'b'], $c, $e];
        self::assertEnumValuesEquals($expectedObjects, from($sourceObjects)->cast('object'));

        // cast (string)
        $sourceObjects = [null, 1, 1.2, '1.3', 'abc', true, false, $e];
        $expectedObjects = ['', '1', '1.2', '1.3', 'abc', '1', '', (string)$e];
        self::assertEnumValuesEquals($expectedObjects, from($sourceObjects)->cast('string'));
    }

    /** @covers \YaLinqo\Enumerable::cast */
    public function testCast_notBuiltinType(): void {
        $this->setExpectedException(InvalidArgumentException::class, Errors::UNSUPPORTED_BUILTIN_TYPE);
        from([ 0 ])->cast('unsupported');
    }

    /** @covers \YaLinqo\Enumerable::ofType */
    public function testOfType(): void {
        $f = static function() { };
        $a = from([
            1, [ 2 ], '6', $f, 1.2, null, new stdClass, 3, 4.5, 'ab', [], new Exception()
        ]);

        // ofType (empty)
        self::assertEnumValuesEquals(
            [],
            E::from([])->ofType('array'));

        // ofType (array)
        self::assertEnumValuesEquals(
            [ [ 2 ], [] ],
            $a->ofType('array'));

        // ofType (int)
        self::assertEnumValuesEquals(
            [ 1, 3 ],
            $a->ofType('int'));
        self::assertEnumValuesEquals(
            [ 1, 3 ],
            $a->ofType('integer'));
        self::assertEnumValuesEquals(
            [ 1, 3 ],
            $a->ofType('long'));

        // ofType (callable)
        self::assertEnumValuesEquals(
            [ $f ],
            $a->ofType('callable'));
        self::assertEnumValuesEquals(
            [ $f ],
            $a->ofType('callback'));

        // ofType (float)
        self::assertEnumValuesEquals(
            [ 1.2, 4.5 ],
            $a->ofType('float'));
        self::assertEnumValuesEquals(
            [ 1.2, 4.5 ],
            $a->ofType('real'));
        self::assertEnumValuesEquals(
            [ 1.2, 4.5 ],
            $a->ofType('double'));

        // ofType (string)
        self::assertEnumValuesEquals(
            [ '6', 'ab' ],
            $a->ofType('string'));

        // ofType (null)
        self::assertEnumValuesEquals(
            [ null ],
            $a->ofType('null'));

        // ofType (numeric)
        self::assertEnumValuesEquals(
            [ 1, '6', 1.2, 3, 4.5 ],
            $a->ofType('numeric'));

        // ofType (scalar)
        self::assertEnumValuesEquals(
            [ 1, '6', 1.2, 3, 4.5, 'ab' ],
            $a->ofType('scalar'));

        // ofType (object)
        self::assertEnumValuesEquals(
            [ $f, new stdClass, new Exception() ],
            $a->ofType('object'));

        // ofType (Exception)
        self::assertEnumValuesEquals(
            [ new Exception() ],
            $a->ofType('Exception'));
    }

    /** @covers \YaLinqo\Enumerable::select */
    public function testSelect(): void {
        // select (selectorValue)
        self::assertEnumEquals(
            [],
            E::from([])->select(static fn($v, $k) => $v+1));
        self::assertEnumEquals(
            [ 4, 5, 6 ],
            E::from([ 3, 4, 5 ])->select(static fn($v, $k) => $v+1));
        self::assertEnumEquals(
            [ 3, 5, 7 ],
            E::from([ 3, 4, 5 ])->select(static fn($v, $k) => $v + $k));

        // select (selectorValue, selectorKey)
        self::assertEnumEquals(
            [ 1 => 3, 2 => 4, 3 => 5 ],
            E::from([ 3, 4, 5 ])->select(static fn($v, $k) => $v, static fn($v, $k) => $k + 1));
        self::assertEnumEquals(
            [ 3 => 3, 5 => 3, 7 => 3 ],
            E::from([ 3, 4, 5 ])->select(static fn($v, $k) => $v-$k, static fn($v, $k) => $v + $k));
    }

    /** @covers \YaLinqo\Enumerable::selectMany */
    public function testSelectMany(): void {
        // selectMany (collectionSelector)
        self::assertEnumEquals(
            [ 1, 2, 3, 4 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany(static fn($v, $k) => $v));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ [ 1 ], [ 2 ], [ 3 ] ])->selectMany(static fn($v, $k) => $v));
        self::assertEnumEquals(
            [ 1, 2 ],
            E::from([ [], [], [ 1, 2 ] ])->selectMany(static fn($v, $k) => $v));
        self::assertEnumEquals(
            [ 1, 2 ],
            E::from([ [ 1, 2 ], [], [] ])->selectMany(static fn($v, $k) => $v));
        self::assertEnumEquals(
            [],
            E::from([ [], [] ])->selectMany(static fn($v, $k) => $v));
        self::assertEnumEquals(
            [],
            E::from([])->selectMany(static fn($v, $k) => $v));

        // selectMany (collectionSelector, resultSelectorValue)
        self::assertEnumEquals(
            [ 0, 0, 1, 1 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany(static fn($v, $k) => $v, static fn($v, $k1) => $k1));
        self::assertEnumEquals(
            [ 1, 3, 3, 5 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany(static fn($v, $k) => $v, static fn($v, $k, $k2) => $v + $k2));

        // selectMany (collectionSelector, resultSelectorValue, resultSelectorKey)
        self::assertEnumEquals(
            [ '00' => 1, '01' => 2, '10' => 3, '11' => 4 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany(static fn($v, $k) => $v, null, static fn($v, $k1, $k2) => "$k1$k2"));
        self::assertEnumEquals(
            [ '00' => 1, '01' => 2, '10' => 4, '11' => 5 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany(static fn($v, $k) => $v, static fn($v, $k1) => $v + $k1, static fn($v, $k1, $k2) => "$k1$k2"));
    }

    /** @covers \YaLinqo\Enumerable::where */
    public function testWhere(): void {
        // where (predicate)
        self::assertEnumEquals(
            [],
            E::from([])->where(Functions::$true));
        self::assertEnumEquals(
            [],
            E::from([])->where(Functions::$false));
        self::assertEnumEquals(
            [ 1, 2, 3, 4 ],
            E::from([ 1, 2, 3, 4 ])->where(Functions::$true));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4 ])->where(Functions::$false));
        self::assertEnumEquals(
            [ 2 => 3, 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->where(static fn($v, $k) => $v>2));
        self::assertEnumEquals(
            [ 0 => '1', 1 => '2' ],
            E::from([ '1', '2', '3', '4' ])->where(static fn($v, $k) => $k<2));
    }

    // endregion

    // region Ordering

    /**
     * @covers \YaLinqo\Enumerable::orderByDir
     * @covers \YaLinqo\OrderedEnumerable
     */
    public function testOrderByDir_asc(): void {
        // orderByDir (false)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderByDir(false));
        self::assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderByDir(false));

        // orderByDir (false, keySelector)
        self::assertEnumValuesEquals(
            [ 6, 5, 4, 3 ],
            E::from([ 4, 6, 5, 3 ])->orderByDir(false, static fn($v, $k) => -$v));
        self::assertEnumValuesEquals(
            [ 2, 3, 1 ],
            E::from([ 'c' => 1, 'a' => 2, 'b' => 3 ])->orderByDir(false, static fn($v, $k) => $k));

        // orderByDir (false, keySelector, comparer)
        $compareLen = static fn($a, $b) => strlen($a) - strlen($b);
        self::assertEnumValuesEquals(
            [ 2, 33, 111, 4444 ],
            E::from([ 111, 2, 33, 4444 ])->orderByDir(false, null, $compareLen));
        self::assertEnumValuesEquals(
            [ 33, 30, 999, 4444 ],
            E::from([ 999, 30, 33, 4444 ])->orderByDir(false, static fn($v, $k) => $v - 33, $compareLen));
        self::assertEnumValuesEquals(
            [ 2, 3, 9, 4 ],
            E::from([ 999 => 9, 2 => 2, 33 => 3, 4444 => 4 ])->orderByDir(false, static fn($v, $k) => $k, $compareLen));

        // both keys and values sorted
        self::assertEnumOrderEquals(
            [ [ 0, 3 ], [ 2, 4 ], [ 1, 5 ] ],
            E::from([ 3, 5, 4 ])->orderByDir(false));
    }

    /**
     * @covers \YaLinqo\Enumerable::orderByDir
     * @covers \YaLinqo\OrderedEnumerable
     */
    public function testOrderByDir_desc(): void {
        // orderByDir (true)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderByDir(true));
        self::assertEnumValuesEquals(
            [ 6, 5, 4, 3 ],
            E::from([ 4, 6, 5, 3 ])->orderByDir(true));

        // orderByDir (true, keySelector)
        self::assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderByDir(true, static fn($v, $k) => -$v));
        self::assertEnumValuesEquals(
            [ 1, 3, 2 ],
            E::from([ 'c' => 1, 'a' => 2, 'b' => 3 ])->orderByDir(true, static fn($v, $k) => $k));

        // orderByDir (true, keySelector, comparer)
        $compareLen = function($a, $b) { return strlen($a) - strlen($b); };
        self::assertEnumValuesEquals(
            [ 4444, 111, 33, 2 ],
            E::from([ 111, 2, 33, 4444 ])->orderByDir(true, null, $compareLen));
        self::assertEnumValuesEquals(
            [ 4444, 999, 30, 33 ],
            E::from([ 999, 30, 33, 4444 ])->orderByDir(true, static fn($v, $k) => $v - 33, $compareLen));
        self::assertEnumValuesEquals(
            [ 4, 9, 3, 2 ],
            E::from([ 999 => 9, 2 => 2, 33 => 3, 4444 => 4 ])->orderByDir(true, static fn($v, $k) => $k, $compareLen));

        // both keys and values sorted
        self::assertEnumOrderEquals(
            [ [ 1, 5 ], [ 2, 4 ], [ 0, 3 ] ],
            from([ 3, 5, 4 ])->orderByDir(true));
    }

    /**
     * @covers \YaLinqo\Enumerable::orderBy
     * @covers \YaLinqo\OrderedEnumerable
     */
    public function testOrderBy(): void {
        // orderBy ()
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy());
        self::assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderBy());

        // orderBy (keySelector)
        self::assertEnumValuesEquals(
            [ 6, 5, 4, 3 ],
            E::from([ 4, 6, 5, 3 ])->orderBy(static fn($v, $k) => -$v));
        self::assertEnumValuesEquals(
            [ 2, 3, 1 ],
            E::from([ 'c' => 1, 'a' => 2, 'b' => 3 ])->orderBy(static fn($v, $k) => $k));

        // orderBy (keySelector, comparer)
        $compareLen = function($a, $b) { return strlen($a) - strlen($b); };
        self::assertEnumValuesEquals(
            [ 2, 33, 111, 4444 ],
            E::from([ 111, 2, 33, 4444 ])->orderBy(null, $compareLen));
        self::assertEnumValuesEquals(
            [ 33, 30, 999, 4444 ],
            E::from([ 999, 30, 33, 4444 ])->orderBy(static fn($v, $k) => $v-33, $compareLen));
        self::assertEnumValuesEquals(
            [ 2, 3, 9, 4 ],
            E::from([ 999 => 9, 2 => 2, 33 => 3, 4444 => 4 ])->orderBy(static fn($v, $k) => $k, $compareLen));

        // both keys and values sorted
        self::assertEnumOrderEquals(
            [ [ 0, 3 ], [ 2, 4 ], [ 1, 5 ] ],
            E::from([ 3, 5, 4 ])->orderBy());
    }

    /**
     * @covers \YaLinqo\Enumerable::orderByDescending
     * @covers \YaLinqo\OrderedEnumerable
     */
    public function testOrderByDescending(): void {
        // orderByDescending ()
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderByDescending());
        self::assertEnumValuesEquals(
            [ 6, 5, 4, 3 ],
            E::from([ 4, 6, 5, 3 ])->orderByDescending());

        // orderByDescending (keySelector)
        self::assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderByDescending(static fn($v, $k) => -$v));
        self::assertEnumValuesEquals(
            [ 1, 3, 2 ],
            E::from([ 'c' => 1, 'a' => 2, 'b' => 3 ])->orderByDescending(static fn($v, $k) => $k));

        // orderByDescending (keySelector, comparer)
        $compareLen = function($a, $b) { return strlen($a) - strlen($b); };
        self::assertEnumValuesEquals(
            [ 4444, 111, 33, 2 ],
            E::from([ 111, 2, 33, 4444 ])->orderByDescending(null, $compareLen));
        self::assertEnumValuesEquals(
            [ 4444, 999, 30, 33 ],
            E::from([ 999, 30, 33, 4444 ])->orderByDescending(static fn($v, $k) => $v-33, $compareLen));
        self::assertEnumValuesEquals(
            [ 4, 9, 3, 2 ],
            E::from([ 999 => 9, 2 => 2, 33 => 3, 4444 => 4 ])->orderByDescending(static fn($v, $k) => $k, $compareLen));

        // both keys and values sorted
        self::assertEnumOrderEquals(
            [ [ 1, 5 ], [ 2, 4 ], [ 0, 3 ] ],
            E::from([ 3, 5, 4 ])->orderByDescending());
    }

    /**
     * @covers \YaLinqo\Enumerable::orderBy
     * @covers \YaLinqo\Enumerable::orderByDescending
     * @covers \YaLinqo\OrderedEnumerable
     */
    public function testOrderBy_onlyLastConsidered(): void {
        self::assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderBy(static fn($v, $k) => -$v)->orderBy(static fn($v, $k) => $v));
        self::assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderBy(static fn($v, $k) => -$v)->orderByDescending(static fn($v, $k) => -$v));
        self::assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderByDescending(static fn($v, $k) => $v)->orderByDescending(static fn($v, $k) => -$v));
    }

    // endregion

    // region Joining and grouping

    /** @covers \YaLinqo\Enumerable::groupJoin */
    public function testGroupJoin(): void {
        // groupJoin (inner)
        self::assertEnumEquals(
            [],
            E::from([])->groupJoin([]));
        self::assertEnumEquals(
            [],
            E::from([])->groupJoin([ 6, 7, 8 ]));
        self::assertEnumEquals(
            [ [ 3, [] ], [ 4, [] ], [ 5, [] ] ],
            E::from([ 3, 4, 5 ])->groupJoin([]));
        self::assertEnumEquals(
            [ [ 3, [ 6 ] ], [ 4, [ 7 ] ], [ 5, [ 8 ] ] ],
            E::from([ 3, 4, 5 ])->groupJoin([ 6, 7, 8 ]));
        self::assertEnumEquals(
            [ 'a' => [ 3, [ 6 ] ], 'b' => [ 4, [ 7 ] ], 'c' => [ 5, [ 8 ] ] ],
            E::from([ 'a' => 3, 'b' => 4, 'c' => 5 ])->groupJoin([ 'a' => 6, 'b' => 7, 'c' => 8 ]));

        // groupJoin (inner, outerKeySelector)
        self::assertEnumEquals(
            [ 3 => [ [ 3, 4 ], [ 6 ] ], 6 => [ [ 5, 6 ], [ 7 ] ], 9 => [ [ 7, 8 ], [ 8 ] ] ],
            E::from([ [ 3, 4 ], [ 5, 6 ], [ 7, 8 ] ])->groupJoin([ 3 => 6, 6 => 7, 9 => 8 ], static fn($v, $k) => $v[0]+$k));

        // groupJoin (inner, outerKeySelector, innerKeySelector)
        self::assertEnumEquals(
            [ 4 => [ 1, [ 3 ] ], 6 => [ 2, [ 4 ] ], 8 => [ 3, [ 5 ] ] ],
            E::from([ 4 => 1, 6 => 2, 8 => 3 ])->groupJoin([ 1 => 3, 2 => 4, 3 => 5 ], null, static fn($v, $k) => $v + $k));
        self::assertEnumEquals(
            [ 4 => [ 4, [ 3 ] ], 6 => [ 6, [ 4 ] ], 8 => [ 8, [ 5 ] ] ],
            E::from([ 3 => 4, 5 => 6, 7 => 8 ])->groupJoin([ 1 => 3, 2 => 4, 3 => 5 ], static fn($v, $k) => $v, static fn($v, $k) => $v + $k));

        // groupJoin (inner, outerKeySelector, innerKeySelector, resultSelectorValue)
        self::assertEnumEquals(
            [ [ 3, [ 6 ] ], [ 5, [ 7 ] ], [ 7, [ 8 ] ] ],
            E::from([ 3, 4, 5 ])->groupJoin([ 6, 7, 8 ], null, null, static fn($v, $e, $k) => [ $v+$k, $e ]));
        self::assertEnumEquals(
            [ 1 => [ [ 6 ], 3 ], 2 => [ [ 7 ], 4 ], 3 => [ [ 8 ], 5 ] ],
            E::from([ 'a1' => 3, 'a2' => 4, 'a3' => 5 ])->groupJoin(
                [ '1b' => 6, '2b' => 7, '3b' => 8 ], static fn($v, $k) => $k[1], static fn($v, $k) => intval($k), static fn($v, $e, $k) => [ $e, $v ]));

        // groupJoin (inner, outerKeySelector, innerKeySelector, resultSelectorValue, resultSelectorKey)
        self::assertEnumEquals(
            [ 6 => [ 'a' ], 7 => [ 'b', 'c' ], 8 => [] ],
            E::from([ [ 1, 6 ], [ 2, 7 ], [ 3, 8 ] ])->groupJoin(
                [ [ 1, 'a' ], [ 2, 'b' ], [ 2, 'c' ], [ 4, 'd' ] ],
                static fn($v, $k) => $v[0], static fn($v, $k) => $v[0], static fn($v, $e, $k) => $e->select(static fn($v, $k) => $v[1]), static fn($v, $k) => $v[1]));
        self::assertEnumEquals(
            [ [ 6, [ 'a' ] ], [ 7, [ 'b', 'c' ] ], [ 8, [] ] ],
            E::from([ [ 1, 6 ], [ 2, 7 ], [ 3, 8 ] ])->groupJoin(
                [ [ 1, 'a' ], [ 2, 'b' ], [ 2, 'c' ], [ 4, 'd' ] ],
                static fn($v, $k) => $v[0], static fn($v, $k) => $v[0], static fn($v, $e, $k) => [ $v[1], $e->select(static fn($v, $k) => $v[1]) ], Functions::increment()));
    }

    /** @covers \YaLinqo\Enumerable::join */
    public function testJoin(): void {
        // join (inner)
        self::assertEnumEquals(
            [],
            E::from([])->join([]));
        self::assertEnumEquals(
            [],
            E::from([])->join([ 6, 7, 8 ]));
        self::assertEnumEquals(
            [],
            E::from([ 3, 4, 5 ])->join([]));
        self::assertEnumEquals(
            [ [ 3, 6 ], [ 4, 7 ], [ 5, 8 ] ],
            E::from([ 3, 4, 5 ])->join([ 6, 7, 8 ]));
        self::assertEnumEquals(
            [ 'a' => [ 3, 6 ], 'b' => [ 4, 7 ], 'c' => [ 5, 8 ] ],
            E::from([ 'a' => 3, 'b' => 4, 'c' => 5 ])->join([ 'a' => 6, 'b' => 7, 'c' => 8 ]));

        // join (inner, outerKeySelector)
        self::assertEnumEquals(
            [ 3 => [ [ 3, 4 ], 6 ], 6 => [ [ 5, 6 ], 7 ], 9 => [ [ 7, 8 ], 8 ] ],
            E::from([ [ 3, 4 ], [ 5, 6 ], [ 7, 8 ] ])->join([ 3 => 6, 6 => 7, 9 => 8 ], static fn($v, $k) => $v[0]+$k));

        // join (inner, outerKeySelector, innerKeySelector)
        self::assertEnumEquals(
            [ 4 => [ 1, 3 ], 6 => [ 2, 4 ], 8 => [ 3, 5 ] ],
            E::from([ 4 => 1, 6 => 2, 8 => 3 ])->join([ 1 => 3, 2 => 4, 3 => 5 ], null, static fn($v, $k) => $v + $k));
        self::assertEnumEquals(
            [ 4 => [ 4, 3 ], 6 => [ 6, 4 ], 8 => [ 8, 5 ] ],
            E::from([ 3 => 4, 5 => 6, 7 => 8 ])->join([ 1 => 3, 2 => 4, 3 => 5 ], static fn($v, $k) => $v, static fn($v, $k) => $v + $k));

        // join (inner, outerKeySelector, innerKeySelector, resultSelectorValue)
        self::assertEnumEquals(
            [ [ 3, 6 ], [ 5, 7 ], [ 7, 8 ] ],
            E::from([ 3, 4, 5 ])->join([ 6, 7, 8 ], null, null, static fn($v1, $v2, $k) => [ $v1+$k, $v2 ]));
        self::assertEnumEquals(
            [ 1 => [ 6, 3 ], 2 => [ 7, 4 ], 3 => [ 8, 5 ] ],
            E::from([ 'a1' => 3, 'a2' => 4, 'a3' => 5 ])->join(
                [ '1b' => 6, '2b' => 7, '3b' => 8 ], static fn($v, $k) => $k[1], static fn($v, $k) => (int) $k, static fn($v1, $v2) => [$v2, $v1 ]));

        // join (inner, outerKeySelector, innerKeySelector, resultSelectorValue, resultSelectorKey)
        self::assertEnumOrderEquals(
            [ [ 6, 'a' ], [ 7, 'b' ], [ 7, 'c' ] ],
            E::from([ [ 1, 6 ], [ 2, 7 ], [ 3, 8 ] ])->join(
                [ [ 1, 'a' ], [ 2, 'b' ], [ 2, 'c' ], [ 4, 'd' ] ],
                static fn($v, $k) => $v[0], static fn($v, $k) => $v[0], static fn($v, $v2) => $v2[1], static fn($v1, $k) => $v1[1]));
        self::assertEnumEquals(
            [ [ 6, 'a' ], [ 7, 'b' ], [ 7, 'c' ] ],
            E::from([ [ 1, 6 ], [ 2, 7 ], [ 3, 8 ] ])->join(
                [ [ 1, 'a' ], [ 2, 'b' ], [ 2, 'c' ], [ 4, 'd' ] ],
                static fn($v, $k) => $v[0], static fn($v, $k) => $v[0], static fn($v1, $v2) => [ $v1[1], $v2[1] ], Functions::increment()));
    }

    /** @covers \YaLinqo\Enumerable::groupBy */
    public function testGroupBy(): void {
        // groupBy ()
        self::assertEnumEquals(
            [],
            E::from([])->groupBy());
        self::assertEnumEquals(
            [ [ 3 ], [ 4 ], [ 5 ] ],
            E::from([ 3, 4, 5 ])->groupBy());
        self::assertEnumEquals(
            [ 'a' => [ 3 ], 'b' => [ 4 ], 'c' => [ 5 ] ],
            E::from([ 'a' => 3, 'b' => 4, 'c' => 5 ])->groupBy());

        // groupBy (keySelector)
        self::assertEnumEquals(
            [ 0 => [ 4, 6, 8 ], 1 => [ 3, 5, 7 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(static fn($v, $k) => $v & 1));
        self::assertEnumEquals(
            [ 0 => [ 4, 6, 8 ], 1 => [ 3, 5, 7 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(static fn($v, $k) => !($k % 2)));

        // groupBy (keySelector, valueSelector)
        self::assertEnumEquals(
            [ [ 3 ], [ 5 ], [ 7 ], [ 9 ], [ 11 ], [ 13 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(null, static fn($v, $k) => $v + $k));
        self::assertEnumEquals(
            [ 0 => [ 5, 9, 13 ], 1 => [ 3, 7, 11 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(static fn($v, $k) => $v&1, static fn($v, $k) => $v + $k));
        self::assertEnumEquals(
            [ 0 => [ 3, 3, 5 ], 1 => [ 3, 3, 4 ] ],
            E::from([ 3, 4, 5, 6, 8, 10 ])->groupBy(static fn($v, $k) => !($k%2), static fn($v, $k) => $v-$k));

        // groupBy (keySelector, valueSelector, resultSelectorValue)
        self::assertEnumEquals(
            [ [ 3, 0 ], [ 4, 1 ], [ 5, 2 ], [ 6, 3 ], [ 7, 4 ], [ 8, 5 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(null, null, static fn($e, $k) => $e+[ 1=>$k ]));
        self::assertEnumEquals(
            [ 0 => [ 4, 6, 8, 'k' => 0 ], 1 => [ 3, 5, 7, 'k' => 1 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(static fn($v, $k) => $v&1, null, static fn($e, $k) => $e+[ "k"=>$k ]));
        self::assertEnumEquals(
            [ [ 3, 0 ], [ 5, 1 ], [ 7, 2 ], [ 9, 3 ], [ 11, 4 ], [ 13, 5 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(null, static fn($v, $k) => $v + $k, static fn($e, $k) => $e+[ 1=>$k ]));
        self::assertEnumEquals(
            [ 0 => [ 5, 9, 13, 'k' => 0 ], 1 => [ 3, 7, 11, 'k' => 1 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(static fn($v, $k) => $v&1, static fn($v, $k) => $v + $k, static fn($e, $k) => $e+[ "k"=>$k ]));

        // groupBy (keySelector, valueSelector, resultSelectorValue, resultSelectorKey)
        self::assertEnumEquals(
            [ 3 => [ 3 ], 5 => [ 4 ], 7 => [ 5 ], 9 => [ 6 ], 11 => [ 7 ], 13 => [ 8 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(null, null, null, static fn($e, $k) => $e[0]+$k));
        self::assertEnumEquals(
            [ 5 => [ 5, 9, 13, 'k' => 0 ], 4 => [ 3, 7, 11, 'k' => 1 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(static fn($v, $k) => $v&1, static fn($v, $k) => $v + $k, static fn($e, $k) => $e+[ "k"=>$k ], static fn($e, $k) => $e[0]+$k));
    }

    /** @covers \YaLinqo\Enumerable::aggregate */
    public function testAggregate(): void {
        // aggregate (func)
        self::assertEquals(
            12,
            E::from([ 3, 4, 5 ])->aggregate(static fn($v, $a) => $a + $v));
        self::assertEquals(
            9, // callback is not called on 1st element, just value is used
            E::from([ 3 => 3, 2 => 4, 1 => 5 ])->aggregate(static fn($v, $a, $k) => $a+$v-$k));

        // aggregate (func, seed)
        self::assertEquals(
            10,
            E::from([])->aggregate(static fn($v, $a) => $a+$v, 10));
        self::assertEquals(
            22,
            E::from([ 3, 4, 5 ])->aggregate(static fn($v, $a) => $a+$v, 10));
        self::assertEquals(
            6,
            E::from([ 3 => 3, 2 => 4, 1 => 5 ])->aggregate(static fn($v, $a, $k) => $a+$v-$k, 0));
    }

    /** @covers \YaLinqo\Enumerable::aggregate */
    public function testAggregate_emptySourceNoSeed(): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_ELEMENTS);
        E::from([])->aggregate(static fn($v, $a) => $a+$v);
    }

    /** @covers \YaLinqo\Enumerable::aggregateOrDefault */
    public function testAggregateOrDefault(): void {
        // aggregate (func)
        self::assertEquals(
            null,
            E::from([])->aggregateOrDefault(static fn($v, $a) => $a+$v));
        self::assertEquals(
            12,
            E::from([ 3, 4, 5 ])->aggregateOrDefault(static fn($v, $a) => $a+$v));
        self::assertEquals(
            9, // callback is not called on 1st element, just value is used
            E::from([ 3 => 3, 2 => 4, 1 => 5 ])->aggregateOrDefault(static fn($v, $a, $k) => $a+$v-$k));

        // aggregate (func, seed)
        self::assertEquals(
            null,
            E::from([])->aggregateOrDefault(static fn($v, $a) => $a+$v, 10));
        self::assertEquals(
            22,
            E::from([ 3, 4, 5 ])->aggregateOrDefault(static fn($v, $a) => $a+$v, 10));
        self::assertEquals(
            6,
            E::from([ 3 => 3, 2 => 4, 1 => 5 ])->aggregateOrDefault(static fn($v, $a, $k) => $a+$v-$k, 0));

        // aggregate (func, seed, default)
        self::assertEquals(
            'empty',
            E::from([])->aggregateOrDefault(static fn($v, $a) => $a+$v, 10, 'empty'));
        self::assertEquals(
            22,
            E::from([ 3, 4, 5 ])->aggregateOrDefault(static fn($v, $a) => $a+$v, 10, 'empty'));
    }

    /** @covers \YaLinqo\Enumerable::average */
    public function testAverage(): void {
        // average ()
        self::assertEquals(
            4,
            E::from([ 3, 4, 5 ])->average());
        self::assertEquals(
            3,
            E::from([ 3, '4', '5', 0 ])->average());

        // average (selector)
        self::assertEquals(
            (3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2) / 3,
            E::from([ 3, 4, 5 ])->average(static fn($v, $k) => $v*2+$k));
        self::assertEquals(
            (3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2 + 0 * 2 + 3) / 4,
            E::from([ 3, '4', '5', 0 ])->average(static fn($v, $k) => $v*2+$k));
    }

    /** @covers \YaLinqo\Enumerable::average */
    public function testAverage_emptySource(): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_ELEMENTS);
        E::from([])->average();
    }

    /** @covers \YaLinqo\Enumerable::count */
    public function testCount(): void {
        // count ()
        self::assertEquals(
            0,
            E::from([])->count());
        self::assertEquals(
            3,
            E::from([ 3, 4, 5 ])->count());
        self::assertEquals(
            4,
            E::from([ 3, '4', '5', 0 ])->count());

        // count (predicate)
        self::assertEquals(
            2,
            E::from([ 3, 4, 5 ])->count(static fn($v, $k) => $v*2+$k<10));
        self::assertEquals(
            3,
            E::from([ 3, '4', '5', 0 ])->count(static fn($v, $k) => $v*2+$k<10));
    }

    /** @covers \YaLinqo\Enumerable::max */
    public function testMax(): void {
        // max ()
        self::assertEquals(
            5,
            E::from([ 3, 5, 4 ])->max());

        // max (selector)
        self::assertEquals(
            5,
            E::from([ 3, 5, 4 ])->max(static fn($v, $k) => $v-$k*3+2)); // 5 4 0
        self::assertEquals(
            5,
            E::from([ 3, '5', '4', 0 ])->max(static fn($v, $k) => $v-$k*3+2)); // 5 4 0 -7
    }

    /** @covers \YaLinqo\Enumerable::max */
    public function testMax_emptySource(): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_ELEMENTS);
        E::from([])->max();
    }

    /** @covers \YaLinqo\Enumerable::maxBy */
    public function testMaxBy(): void {
        $compare = function($a, $b) { return strcmp($a * $a, $b * $b); };

        // max ()
        self::assertEquals(
            3,
            E::from([ 2, 3, 5, 4 ])->maxBy($compare));

        // max (selector)
        self::assertEquals(
            8,
            E::from([ 2, 0, 3, 5, 6 ])->maxBy($compare, static fn($v, $k) => $v + $k)); // 2 1 5 8 10
        self::assertEquals(
            7,
            E::from([ '5', 3, false, '4' ])->maxBy($compare, static fn($v, $k) => $v + $k)); // 5 4 2 7
    }

    /** @covers \YaLinqo\Enumerable::maxBy */
    public function testMaxBy_emptySource(): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_ELEMENTS);
        $compare = function($a, $b) { return strcmp($a * $a, $b * $b); };
        E::from([])->maxBy($compare);
    }

    /** @covers \YaLinqo\Enumerable::min */
    public function testMin(): void {
        // min ()
        self::assertEquals(
            3,
            E::from([ 3, 5, 4 ])->min());

        // min (selector)
        self::assertEquals(
            0,
            E::from([ 3, 5, 4 ])->min(static fn($v, $k) => $v-$k*3+2)); // 5 4 0
        self::assertEquals(
            -7,
            E::from([ 3, '5', '4', false ])->min(static fn($v, $k) => $v-$k*3+2)); // 5 4 0 -7
    }

    /** @covers \YaLinqo\Enumerable::min */
    public function testMin_emptySource(): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_ELEMENTS);
        E::from([])->min();
    }

    /** @covers \YaLinqo\Enumerable::minBy */
    public function testMinBy(): void {
        $compare = function($a, $b) { return strcmp($a * $a, $b * $b); };

        // min ()
        self::assertEquals(
            4,
            E::from([ 2, 3, 5, 4 ])->minBy($compare));

        // min (selector)
        self::assertEquals(
            1,
            E::from([ 2, 0, 3, 5, 6 ])->minBy($compare, static fn($v, $k) => $v + $k)); // 2 1 5 8 10
        self::assertEquals(
            4,
            E::from([ '5', 3, 0, '4' ])->minBy($compare, static fn($v, $k) => $v + $k)); // 5 4 2 7
    }

    /** @covers \YaLinqo\Enumerable::minBy */
    public function testMinBy_emptySource(): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_ELEMENTS);
        $compare = function($a, $b) { return strcmp($a * $a, $b * $b); };
        E::from([])->minBy($compare);
    }

    /** @covers \YaLinqo\Enumerable::sum */
    public function testSum(): void {
        // sum ()
        self::assertEquals(
            0,
            E::from([])->sum());
        self::assertEquals(
            12,
            E::from([ 3, 4, 5 ])->sum());
        self::assertEquals(
            12,
            E::from([ 3, '4', '5', false ])->sum());

        // sum (selector)
        self::assertEquals(
            3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2,
            E::from([ 3, 4, 5 ])->sum(static fn($v, $k) => $v*2+$k));
        self::assertEquals(
            3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2 + 0 * 2 + 3,
            E::from([ 3, '4', '5', null ])->sum(static fn($v, $k) => $v*2+$k));
    }

    /** @covers \YaLinqo\Enumerable::all */
    public function testAll(): void {
        // all (predicate)
        self::assertEquals(
            true,
            E::from([])->all(static fn($v, $k) => $v>0));
        self::assertEquals(
            true,
            E::from([ 1, 2, 3 ])->all(static fn($v, $k) => $v > 0));
        self::assertEquals(
            false,
            E::from([ 1, -2, 3 ])->all(static fn($v, $k) => $v > 0));
        self::assertEquals(
            false,
            E::from([ -1, -2, -3 ])->all(static fn($v, $k) => $v > 0));
    }

    /** @covers \YaLinqo\Enumerable::any */
    public function testAny_array(): void {
        // any ()
        self::assertEquals(
            false,
            E::from([])->any());
        self::assertEquals(
            true,
            E::from([ 1, 2, 3 ])->any());

        // any (predicate)
        self::assertEquals(
            false,
            E::from([])->any(static fn($v, $k) => $v > 0));
        self::assertEquals(
            true,
            E::from([ 1, 2, 3 ])->any(static fn($v, $k) => $v > 0));
        self::assertEquals(
            true,
            E::from([ 1, -2, 3 ])->any(static fn($v, $k) => $v > 0));
        self::assertEquals(
            false,
            E::from([ -1, -2, -3 ])->any(static fn($v, $k) => $v > 0));
    }

    /** @covers \YaLinqo\Enumerable::any */
    public function testAny_fromEnumerable(): void {
        // any ()
        self::assertEquals(
            false,
            E::from([])->select(static fn($v, $k) => $v)->any());
        self::assertEquals(
            true,
            E::from([ 1, 2, 3 ])->select(static fn($v, $k) => $v)->any());

        // any (predicate)
        self::assertEquals(
            false,
            E::from([])->select(static fn($v, $k) => $v)->any(static fn($v, $k) => $v > 0));
        self::assertEquals(
            true,
            E::from([ 1, 2, 3 ])->select(static fn($v, $k) => $v)->any(static fn($v, $k) => $v > 0));
        self::assertEquals(
            true,
            E::from([ 1, -2, 3 ])->select(static fn($v, $k) => $v)->any(static fn($v, $k) => $v > 0));
        self::assertEquals(
            false,
            E::from([ -1, -2, -3 ])->select(static fn($v, $k) => $v)->any(static fn($v, $k) => $v > 0));
    }

    /** @covers \YaLinqo\Enumerable::append */
    public function testAppend(): void {
        // append (value)
        self::assertEnumEquals(
            [ null => 9 ],
            E::from([])->append(9));
        self::assertEnumEquals(
            [ 0 => 1, 1 => 3, null => 9 ],
            E::from([ 1, 3 ])->append(9));

        // append (value, key)
        self::assertEnumEquals(
            [ 2 => 9 ],
            E::from([])->append(9, 2));
        self::assertEnumEquals(
            [ 0 => 1, 1 => 3, 8 => 9 ],
            E::from([ 1, 3 ])->append(9, 8));
    }

    /** @covers \YaLinqo\Enumerable::concat */
    public function testConcat(): void {
        // concat ()
        self::assertEnumEquals(
            [],
            E::from([])->concat([]));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->concat([]));
        self::assertEnumEquals(
            [ 1, 2, 3, 3 ],
            E::from([])->concat([ 1, 2, 3, 3 ]));
        self::assertEnumOrderEquals(
            [ [ 0, 1 ], [ 1, 2 ], [ 2, 2 ], [ 0, 1 ], [ 1, 3 ] ],
            E::from([ 1, 2, 2 ])->concat([ 1, 3 ]));
    }

    /** @covers \YaLinqo\Enumerable::contains */
    public function testContains(): void {
        // contains (value)
        self::assertEquals(
            false,
            E::from([])->contains(2));
        self::assertEquals(
            true,
            E::from([ 1, 2, 3 ])->contains(2));
        self::assertEquals(
            false,
            E::from([ 1, 2, 3 ])->contains(4));
    }

    /** @covers \YaLinqo\Enumerable::distinct */
    public function testDistinct(): void {
        // distinct ()
        self::assertEnumEquals(
            [],
            E::from([])->distinct());
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->distinct());
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3, 1, 2 ])->distinct());

        // distinct (keySelector)
        self::assertEnumEquals(
            [],
            E::from([])->distinct(static fn($v, $k) => $v*$k));
        self::assertEnumEquals(
            [ 3 => 1, 2 => 2, 1 => 5 ],
            E::from([ 3 => 1, 2 => 2, 1 => 5 ])->distinct(static fn($v, $k) => $v*$k));
        self::assertEnumEquals(
            [ 4 => 1, 1 => 3 ],
            E::from([ 4 => 1, 2 => 2, 1 => 3 ])->distinct(static fn($v, $k) => $v*$k));
    }

    /** @covers \YaLinqo\Enumerable::except */
    public function testExcept(): void {
        // except ()
        self::assertEnumEquals(
            [],
            E::from([])->except([]));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->except([]));
        self::assertEnumEquals(
            [],
            E::from([])->except([ 1, 2, 3 ]));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->except([ 1, 2, 3 ]));

        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->except([ '1', '2', '3' ]));
        self::assertEnumEquals(
            [],
            E::from([ '1', '2', '3' ])->except([ 1, 2, 3 ]));
        self::assertEnumEquals(
            [],
            E::from([ 1, '2', 3 ])->except([ '1', 2, '3' ]));

        self::assertEnumEquals(
            [ 1 => 2, 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->except([ 1, 3 ]));
        self::assertEnumEquals(
            [ 1 => 2, 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->except([ 1, 3, 5, 7 ]));

        // except (keySelector)
        self::assertEnumEquals(
            [],
            E::from([])->except([], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->except([], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [],
            E::from([])->except([ 1, 2, 3 ], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->except([ 1, 2, 3 ], static fn($v, $k) => $k));

        self::assertEnumEquals(
            [ 2 => 3, 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->except([ 1, 3 ], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [ 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->except([ 1, 3, 5 ], static fn($v, $k) => $k));
    }

    /** @covers \YaLinqo\Enumerable::intersect */
    public function testIntersect(): void {
        // intersect ()
        self::assertEnumEquals(
            [],
            E::from([])->intersect([]));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->intersect([]));
        self::assertEnumEquals(
            [],
            E::from([])->intersect([ 1, 2, 3 ]));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->intersect([ 1, 2, 3 ]));

        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->intersect([ '1', '2', '3' ]));
        self::assertEnumEquals(
            [ '1', '2', '3' ],
            E::from([ '1', '2', '3' ])->intersect([ 1, 2, 3 ]));
        self::assertEnumEquals(
            [ 1, '2', 3 ],
            E::from([ 1, '2', 3 ])->intersect([ '1', 2, '3' ]));

        self::assertEnumEquals(
            [ 0 => 1, 2 => 3 ],
            E::from([ 1, 2, 3, 4 ])->intersect([ 1, 3 ]));
        self::assertEnumEquals(
            [ 0 => 1, 2 => 3 ],
            E::from([ 1, 2, 3, 4 ])->intersect([ 1, 3, 5, 7 ]));

        // intersect (keySelector)
        self::assertEnumEquals(
            [],
            E::from([])->intersect([], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->intersect([], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [],
            E::from([])->intersect([ 1, 2, 3 ], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->intersect([ 1, 2, 3 ], static fn($v, $k) => $k));

        self::assertEnumEquals(
            [ 0 => 1, 1 => 2 ],
            E::from([ 1, 2, 3, 4 ])->intersect([ 1, 3 ], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [ 0 => 1, 1 => 2, 2 => 3 ],
            E::from([ 1, 2, 3, 4 ])->intersect([ 1, 3, 5 ], static fn($v, $k) => $k));
    }

    /** @covers \YaLinqo\Enumerable::prepend */
    public function testPrepend(): void {
        // prepend (value)
        self::assertEnumEquals(
            [ null => 9 ],
            E::from([])->prepend(9));
        self::assertEnumEquals(
            [ null => 9, 0 => 1, 1 => 3 ],
            E::from([ 1, 3 ])->prepend(9));

        // prepend (value, key)
        self::assertEnumEquals(
            [ 2 => 9 ],
            E::from([])->prepend(9, 2));
        self::assertEnumEquals(
            [ 8 => 9, 0 => 1, 1 => 3 ],
            E::from([ 1, 3 ])->prepend(9, 8));
    }

    /** @covers \YaLinqo\Enumerable::union */
    public function testUnion(): void {
        // union ()
        self::assertEnumEquals(
            [],
            E::from([])->union([]));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([]));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([])->union([ 1, 2, 3, 3 ]));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3, 3 ])->union([ 1, 2, 3 ]));

        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([ '1', '2', '3' ]));
        self::assertEnumEquals(
            [ '1', '2', '3' ],
            E::from([ '1', '2', '3' ])->union([ 1, 2, 3 ]));
        self::assertEnumEquals(
            [ 1, '2', 3 ],
            E::from([ 1, '2', 3 ])->union([ '1', 2, '3' ]));

        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([ 1, 3 ]));
        self::assertEnumOrderEquals(
            [ [ 0, 1 ], [ 1, 2 ], [ 2, 3 ], [ 2, 5 ] ],
            E::from([ 1, 2, 3 ])->union([ 1, 3, 5 ]));

        // union (keySelector)
        self::assertEnumEquals(
            [],
            E::from([])->union([], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([])->union([ 1, 2, 3 ], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([ 1, 2, 3 ], static fn($v, $k) => $k));

        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([ 1, 3 ], static fn($v, $k) => $k));
        self::assertEnumEquals(
            [ 1, 2, 3, 7 ],
            E::from([ 1, 2, 3 ])->union([ 1, 3, 5, 7 ], static fn($v, $k) => $k));
    }

    // endregion

    // region Pagination

    /** @covers \YaLinqo\Enumerable::elementAt */
    public function testElementAt_array(): void {
        // elementAt (key)
        self::assertEquals(
            2,
            E::from([ 1, 2, 3 ])->elementAt(1));
        self::assertEquals(
            2,
            E::from([ 3 => 1, 2, 'a' => 3 ])->elementAt(4));
    }

    /** @covers \YaLinqo\Enumerable::elementAt */
    public function testElementAt_enumerable(): void {
        // elementAt (key)
        self::assertEquals(
            2,
            E::from([ 1, 2, 3 ])->select(static fn($v, $k) => $v)->elementAt(1));
        self::assertEquals(
            2,
            E::from([ 3 => 1, 2, 'a' => 3 ])->select(static fn($v, $k) => $v)->elementAt(4));
    }

    /**
     * @covers \YaLinqo\Enumerable::elementAt
     * @dataProvider dataProvider_testElementAt_noKey
     * @param E $enum
     * @param mixed $key
     */
    public function testElementAt_noKey(E $enum, mixed $key): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_KEY);
        $enum->elementAt($key);
    }

    public function dataProvider_testElementAt_noKey(): array {
        return [
            // array source
            [ E::from([]), 1 ],
            [ E::from([ 1, 2, 3 ]), 4 ],
            [ E::from([ 'a' => 1, 'b' => 2, 'c' => 3 ]), 0 ],
            // Enumerable source
            [ E::from([])->select(static fn($v, $k) => $v), 1 ],
            [ E::from([ 1, 2, 3 ])->select(static fn($v, $k) => $v), 4 ],
            [ E::from([ 'a' => 1, 'b' => 2, 'c' => 3 ])->select(static fn($v, $k) => $v), 0 ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::elementAtOrDefault */
    public function testElementAtOrDefault_array(): void {
        // elementAtOrDefault (key)
        self::assertEquals(
            null,
            E::from([])->elementAtOrDefault(1));
        self::assertEquals(
            2,
            E::from([ 1, 2, 3 ])->elementAtOrDefault(1));
        self::assertEquals(
            null,
            E::from([ 1, 2, 3 ])->elementAtOrDefault(4));
        self::assertEquals(
            2,
            E::from([ 3 => 1, 2, 'a' => 3 ])->elementAtOrDefault(4));
        self::assertEquals(
            null,
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3 ])->elementAtOrDefault(0));
    }

    /** @covers \YaLinqo\Enumerable::elementAtOrDefault */
    public function testElementAtOrDefault_enumerable(): void {
        // elementAtOrDefault (key)
        self::assertEquals(
            null,
            E::from([])->select(static fn($v, $k) => $v)->elementAtOrDefault(1));
        self::assertEquals(
            2,
            E::from([ 1, 2, 3 ])->select(static fn($v, $k) => $v)->elementAtOrDefault(1));
        self::assertEquals(
            null,
            E::from([ 1, 2, 3 ])->select(static fn($v, $k) => $v)->elementAtOrDefault(4));
        self::assertEquals(
            2,
            E::from([ 3 => 1, 2, 'a' => 3 ])->select(static fn($v, $k) => $v)->elementAtOrDefault(4));
        self::assertEquals(
            null,
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3 ])->select(static fn($v, $k) => $v)->elementAtOrDefault(0));
    }

    /** @covers \YaLinqo\Enumerable::first */
    public function testFirst(): void {
        // first ()
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->first());
        self::assertEquals(
            1,
            E::from([ 3 => 1, 2, 'a' => 3 ])->first());

        // first (predicate)
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->first(static fn($v, $k) => $v > 0));
        self::assertEquals(
            2,
            E::from([ -1, 2, 3 ])->first(static fn($v, $k) => $v > 0));
    }

    /**
     * @covers \YaLinqo\Enumerable::first
     * @dataProvider dataProvider_testFirst_noMatches
     */
    public function testFirst_noMatches($source, $predicate): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_MATCHES);
        E::from($source)->first($predicate);
    }

    public function dataProvider_testFirst_noMatches(): array {
        return [
            // first ()
            [ [], null ],
            // first (predicate)
            [ [], static fn($v, $k) => $v > 0 ],
            [ [ -1, -2, -3 ], static fn($v, $k) => $v > 0 ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::firstOrDefault */
    public function testFirstOrDefault(): void {
        // firstOrDefault ()
        self::assertEquals(
            null,
            E::from([])->firstOrDefault());
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrDefault());
        self::assertEquals(
            1,
            E::from([ 3 => 1, 2, 'a' => 3 ])->firstOrDefault());

        // firstOrDefault (default)
        self::assertEquals(
            'a',
            E::from([])->firstOrDefault('a'));
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrDefault('a'));
        self::assertEquals(
            1,
            E::from([ 3 => 1, 2, 'a' => 3 ])->firstOrDefault('a'));

        // firstOrDefault (default, predicate)
        self::assertEquals(
            'a',
            E::from([])->firstOrDefault('a', static fn($v, $k) => $v > 0));
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrDefault('a', static fn($v, $k) => $v > 0));
        self::assertEquals(
            2,
            E::from([ -1, 2, 3 ])->firstOrDefault('a', static fn($v, $k) => $v > 0));
        self::assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->firstOrDefault('a', static fn($v, $k) => $v > 0));
    }

    /** @covers \YaLinqo\Enumerable::firstOrFallback */
    public function testFirstOrFallback(): void {
        $fallback = function() { return 'a'; };

        // firstOrFallback (fallback)
        self::assertEquals(
            'a',
            E::from([])->firstOrFallback($fallback));
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrFallback($fallback));
        self::assertEquals(
            1,
            E::from([ 3 => 1, 2, 'a' => 3 ])->firstOrFallback($fallback));

        // firstOrFallback (fallback, predicate)
        self::assertEquals(
            'a',
            E::from([])->firstOrFallback($fallback, static fn($v, $k) => $v > 0));
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrFallback($fallback, static fn($v, $k) => $v > 0));
        self::assertEquals(
            2,
            E::from([ -1, 2, 3 ])->firstOrFallback($fallback, static fn($v, $k) => $v > 0));
        self::assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->firstOrFallback($fallback, static fn($v, $k) => $v > 0));
    }

    /** @covers \YaLinqo\Enumerable::last */
    public function testLast(): void {
        // last ()
        self::assertEquals(
            3,
            E::from([ 1, 2, 3 ])->last());
        self::assertEquals(
            3,
            E::from([ 3 => 1, 2, 'a' => 3 ])->last());

        // last (predicate)
        self::assertEquals(
            3,
            E::from([ 1, 2, 3 ])->last(static fn($v, $k) => $v > 0));
        self::assertEquals(
            2,
            E::from([ 1, 2, -3 ])->last(static fn($v, $k) => $v > 0));
    }

    /**
     * @covers \YaLinqo\Enumerable::last
     * @dataProvider dataProvider_testLast_noMatches
     */
    public function testLast_noMatches($source, $predicate): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_MATCHES);
        E::from($source)->last($predicate);
    }

    public function dataProvider_testLast_noMatches(): array {
        return [
            // last ()
            [ [], null ],
            // last (predicate)
            [ [], static fn($v, $k) => $v > 0 ],
            [ [ -1, -2, -3 ], static fn($v, $k) => $v > 0 ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::lastOrDefault */
    public function testLastOrDefault(): void {
        // lastOrDefault ()
        self::assertEquals(
            null,
            E::from([])->lastOrDefault());
        self::assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrDefault());
        self::assertEquals(
            3,
            E::from([ 3 => 1, 2, 'a' => 3 ])->lastOrDefault());

        // lastOrDefault (default)
        self::assertEquals(
            'a',
            E::from([])->lastOrDefault('a'));
        self::assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrDefault('a'));
        self::assertEquals(
            3,
            E::from([ 3 => 1, 2, 'a' => 3 ])->lastOrDefault('a'));

        // lastOrDefault (default, predicate)
        self::assertEquals(
            'a',
            E::from([])->lastOrDefault('a', static fn($v, $k) => $v > 0));
        self::assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrDefault('a', static fn($v, $k) => $v > 0));
        self::assertEquals(
            2,
            E::from([ 1, 2, -3 ])->lastOrDefault('a', static fn($v, $k) => $v > 0));
        self::assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->lastOrDefault('a', static fn($v, $k) => $v > 0));
    }

    /** @covers \YaLinqo\Enumerable::lastOrFallback */
    public function testLastOrFallback(): void {
        $fallback = function() { return 'a'; };

        // lastOrFallback (fallback)
        self::assertEquals(
            'a',
            E::from([])->lastOrFallback($fallback));
        self::assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrFallback($fallback));
        self::assertEquals(
            3,
            E::from([ 3 => 1, 2, 'a' => 3 ])->lastOrFallback($fallback));

        // lastOrFallback (fallback, predicate)
        self::assertEquals(
            'a',
            E::from([])->lastOrFallback($fallback, static fn($v, $k) => $v > 0));
        self::assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrFallback($fallback, static fn($v, $k) => $v > 0));
        self::assertEquals(
            2,
            E::from([ 1, 2, -3 ])->lastOrFallback($fallback, static fn($v, $k) => $v > 0));
        self::assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->lastOrFallback($fallback, static fn($v, $k) => $v > 0));
    }

    /** @covers \YaLinqo\Enumerable::single */
    public function testSingle(): void {
        // single ()
        self::assertEquals(
            2,
            E::from([ 2 ])->single());

        // single (predicate)
        self::assertEquals(
            2,
            E::from([ -1, 2, -3 ])->single(static fn($v, $k) => $v > 0));
    }

    /**
     * @covers \YaLinqo\Enumerable::single
     * @dataProvider dataProvider_testSingle_noMatches
     */
    public function testSingle_noMatches($source, $predicate): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::NO_MATCHES);
        E::from($source)->single($predicate);
    }

    public function dataProvider_testSingle_noMatches(): array {
        return [
            // single ()
            [ [], null ],
            // single (predicate)
            [ [], static fn($v, $k) => $v > 0 ],
            [ [ -1, -2, -3 ], static fn($v, $k) => $v > 0 ],
        ];
    }

    /**
     * @covers \YaLinqo\Enumerable::single
     * @dataProvider dataProvider_testSingle_manyMatches
     */
    public function testSingle_manyMatches($source, $predicate): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::MANY_MATCHES);
        E::from($source)->single($predicate);
    }

    public function dataProvider_testSingle_manyMatches(): array {
        return [
            // single ()
            [ [ 1, 2, 3 ], null, null ],
            [ [ 3 => 1, 2, 'a' => 3 ], null, null ],
            // single (predicate)
            [ [ 1, 2, 3 ], static fn($v, $k) => $v > 0 ],
            [ [ 1, 2, -3 ], static fn($v, $k) => $v > 0 ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::singleOrDefault */
    public function testSingleOrDefault(): void {
        // singleOrDefault ()
        self::assertEquals(
            null,
            E::from([])->singleOrDefault());
        self::assertEquals(
            2,
            E::from([ 2 ])->singleOrDefault());

        // singleOrDefault (default)
        self::assertEquals(
            'a',
            E::from([])->singleOrDefault('a'));
        self::assertEquals(
            2,
            E::from([ 2 ])->singleOrDefault('a'));

        // singleOrDefault (default, predicate)
        self::assertEquals(
            'a',
            E::from([])->singleOrDefault('a', static fn($v, $k) => $v > 0));
        self::assertEquals(
            2,
            E::from([ -1, 2, -3 ])->singleOrDefault('a', static fn($v, $k) => $v > 0));
        self::assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->singleOrDefault('a', static fn($v, $k) => $v > 0));
    }

    /**
     * @covers \YaLinqo\Enumerable::singleOrDefault
     * @dataProvider dataProvider_testSingleOrDefault_manyMatches
     */
    public function testSingleOrDefault_manyMatches($source, $default, $predicate): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::MANY_MATCHES);
        E::from($source)->singleOrDefault($default, $predicate);
    }

    public function dataProvider_testSingleOrDefault_manyMatches(): array {
        return [
            // singleOrDefault ()
            [ [ 1, 2, 3 ], null, null ],
            [ [ 3 => 1, 2, 'a' => 3 ], null, null ],
            // singleOrDefault (default)
            [ [ 1, 2, 3 ], 'a', null ],
            [ [ 3 => 1, 2, 'a' => 3 ], 'a', null ],
            // singleOrDefault (default, predicate)
            [ [ 1, 2, 3 ], 'a', static fn($v, $k) => $v > 0 ],
            [ [ 1, 2, -3 ], 'a', static fn($v, $k) => $v > 0 ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::singleOrFallback */
    public function testSingleOrFallback(): void {
        $fallback = function() { return 'a'; };

        // singleOrFallback (fallback)
        self::assertEquals(
            'a',
            E::from([])->singleOrFallback($fallback));
        self::assertEquals(
            2,
            E::from([ 2 ])->singleOrFallback($fallback));

        // singleOrFallback (fallback, predicate)
        self::assertEquals(
            'a',
            E::from([])->singleOrFallback($fallback, static fn($v, $k) => $v > 0));
        self::assertEquals(
            2,
            E::from([ -1, 2, -3 ])->singleOrFallback($fallback, static fn($v, $k) => $v > 0));
        self::assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->singleOrFallback($fallback, static fn($v, $k) => $v > 0));
    }

    /**
     * @covers \YaLinqo\Enumerable::singleOrFallback
     * @dataProvider dataProvider_testSingleOrFallback_manyMatches
     */
    public function testSingleOrFallback_manyMatches($source, $fallback, $predicate): void {
        $this->setExpectedException(UnexpectedValueException::class, Errors::MANY_MATCHES);
        E::from($source)->singleOrFallback($fallback, $predicate);
    }

    public function dataProvider_testSingleOrFallback_manyMatches(): array {
        $fallback = static function() { return 'a'; };

        return [
            // singleOrFallback ()
            [ [ 1, 2, 3 ], null, null ],
            [ [ 3 => 1, 2, 'a' => 3 ], null, null ],
            // singleOrFallback (fallback)
            [ [ 1, 2, 3 ], $fallback, null ],
            [ [ 3 => 1, 2, 'a' => 3 ], $fallback, null ],
            // singleOrFallback (fallback, predicate)
            [ [ 1, 2, 3 ], $fallback, static fn($v, $k) => $v > 0 ],
            [ [ 1, 2, -3 ], $fallback, static fn($v, $k) => $v > 0 ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::indexOf */
    public function testIndexOf(): void {
        $i = function($v) { return $v; };

        // array.indexOf (value)
        self::assertEquals(
            false,
            E::from([])->indexOf('a'));
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->indexOf(2));
        self::assertEquals(
            false,
            E::from([ 1, 2, 3 ])->indexOf(4));
        self::assertEquals(
            1,
            E::from([ 1, 2, 3, 2, 1 ])->indexOf(2));
        self::assertEquals(
            4,
            E::from([ 3 => 1, 2, 2, 'a' => 3 ])->indexOf(2));

        // iterator.indexOf (value)
        self::assertEquals(
            false,
            E::from([])->select($i)->indexOf('a'));
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->select($i)->indexOf(2));
        self::assertEquals(
            false,
            E::from([ 1, 2, 3 ])->select($i)->indexOf(4));
        self::assertEquals(
            1,
            E::from([ 1, 2, 3, 2, 1 ])->select($i)->indexOf(2));
        self::assertEquals(
            4,
            E::from([ 3 => 1, 2, 2, 'a' => 3 ])->select($i)->indexOf(2));
    }

    /** @covers \YaLinqo\Enumerable::lastIndexOf */
    public function testLastIndexOf(): void {
        // indexOf (value)
        self::assertEquals(
            null,
            E::from([])->lastIndexOf('a'));
        self::assertEquals(
            1,
            E::from([ 1, 2, 3 ])->lastIndexOf(2));
        self::assertEquals(
            3,
            E::from([ 1, 2, 3, 2, 1 ])->lastIndexOf(2));
        self::assertEquals(
            5,
            E::from([ 3 => 1, 2, 2, 'a' => 3 ])->lastIndexOf(2));
    }

    /** @covers \YaLinqo\Enumerable::findIndex */
    public function testFindIndex(): void {
        self::assertEquals(
            null,
            E::from([])->findIndex(static fn($v, $k) => $v > 0));
        self::assertEquals(
            0,
            E::from([ 1, 2, 3, 4 ])->findIndex(static fn($v, $k) => $v > 0));
        self::assertEquals(
            1,
            E::from([ -1, 2, 3, -4 ])->findIndex(static fn($v, $k) => $v > 0));
        self::assertEquals(
            null,
            E::from([ -1, -2, -3, -4 ])->findIndex(static fn($v, $k) => $v > 0));
    }

    /** @covers \YaLinqo\Enumerable::findLastIndex */
    public function testFindLastIndex(): void {
        self::assertEquals(
            null,
            E::from([])->findLastIndex(static fn($v, $k) => $v > 0));
        self::assertEquals(
            3,
            E::from([ 1, 2, 3, 4 ])->findLastIndex(static fn($v, $k) => $v > 0));
        self::assertEquals(
            2,
            E::from([ -1, 2, 3, -4 ])->findLastIndex(static fn($v, $k) => $v > 0));
        self::assertEquals(
            null,
            E::from([ -1, -2, -3, -4 ])->findLastIndex(static fn($v, $k) => $v > 0));
    }

    /** @covers \YaLinqo\Enumerable::skip */
    public function testSkip(): void {
        self::assertEnumEquals(
            [],
            E::from([])->skip(-2));
        self::assertEnumEquals(
            [],
            E::from([])->skip(0));
        self::assertEnumEquals(
            [],
            E::from([])->skip(2));
        self::assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skip(-2));
        self::assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skip(0));
        self::assertEnumEquals(
            [ 2 => 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skip(2));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->skip(5));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->skip(6));
        self::assertEnumEquals(
            [ 'c' => 3, 'd' => 4, 'e' => 5 ],
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ])->skip(2));
    }

    /** @covers \YaLinqo\Enumerable::skipWhile */
    public function testSkipWhile(): void {
        self::assertEnumEquals(
            [],
            E::from([])->skipWhile(static fn($v, $k) => $v > 2));
        self::assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skipWhile(static fn($v, $k) => $v<0));
        self::assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skipWhile(static fn($v, $k) => $k === -1));
        self::assertEnumEquals(
            [ 2 => 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skipWhile(static fn($v, $k) => $v + $k<4));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->skipWhile(static fn($v, $k) => $v > 0));
        self::assertEnumEquals(
            [ 'c' => 3, 'd' => 4, 'e' => 5 ],
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ])->skipWhile(static fn($v, $k) => $k<"c"));
    }

    /** @covers \YaLinqo\Enumerable::take */
    public function testTake(): void {
        self::assertEnumEquals(
            [],
            E::from([])->take(-2));
        self::assertEnumEquals(
            [],
            E::from([])->take(0));
        self::assertEnumEquals(
            [],
            E::from([])->take(2));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->take(-2));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->take(0));
        self::assertEnumEquals(
            [ 1, 2 ],
            E::from([ 1, 2, 3, 4, 5 ])->take(2));
        self::assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->take(5));
        self::assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->take(6));
        self::assertEnumEquals(
            [ 'a' => 1, 'b' => 2 ],
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ])->take(2));
    }

    /** @covers \YaLinqo\Enumerable::takeWhile */
    public function testTakeWhile(): void {
        self::assertEnumEquals(
            [],
            E::from([])->takeWhile(static fn($v, $k) => $v > 2));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->takeWhile(static fn($v, $k) => $v<0));
        self::assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->takeWhile(static fn($v, $k) => $k === -1));
        self::assertEnumEquals(
            [ 1, 2 ],
            E::from([ 1, 2, 3, 4, 5 ])->takeWhile(static fn($v, $k) => $v + $k <4));
        self::assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->takeWhile(static fn($v, $k) => $v > 0));
        self::assertEnumEquals(
            [ 'a' => 1, 'b' => 2 ],
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ])->takeWhile(static fn($v, $k) => $k<"c"));
    }

    // endregion

    // region Conversion

    /** @covers \YaLinqo\Enumerable::toArray */
    public function testToArray_array(): void {
        self::assertEquals(
            [],
            E::from([])->toArray());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toArray());
        self::assertEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toArray());
    }

    /** @covers \YaLinqo\Enumerable::toArray */
    public function testToArray_enumerable(): void {
        self::assertEquals(
            [],
            E::from([])->select(static fn($v, $k) => $v)->toArray());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->select(static fn($v, $k) => $v)->toArray());
        self::assertEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->select(static fn($v, $k) => $v)->toArray());
    }

    /**
     * @covers \YaLinqo\Enumerable::toArrayDeep
     * @covers \YaLinqo\Enumerable::toArrayDeepProc
     */
    public function testToArrayDeep(): void {
        self::assertEquals(
            [],
            E::from([])->toArrayDeep());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toArrayDeep());
        self::assertEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toArrayDeep());
        self::assertEquals(
            [ 1, 2, 6 => [ 7 => [ 'a' => 'a' ], [ 8 => 4, 5 ] ] ],
            E::from([ 1, 2, 6 => E::from([ 7 => [ 'a' => 'a' ], E::from([ 8 => 4, 5 ]) ]) ])->toArrayDeep());
    }

    /** @covers \YaLinqo\Enumerable::toList */
    public function testToList_array(): void {
        self::assertEquals(
            [],
            E::from([])->toList());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toList());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toList());
    }

    /** @covers \YaLinqo\Enumerable::toList */
    public function testToList_enumerable(): void {
        self::assertEquals(
            [],
            E::from([])->select(static fn($v, $k) => $v)->toList());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->select(static fn($v, $k) => $v)->toList());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->select(static fn($v, $k) => $v)->toList());
    }

    /**
     * @covers \YaLinqo\Enumerable::toListDeep
     * @covers \YaLinqo\Enumerable::toListDeepProc
     */
    public function testToListDeep(): void {
        self::assertEquals(
            [],
            E::from([])->toListDeep());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toListDeep());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toListDeep());
        self::assertEquals(
            [ 1, 2, [ [ 'a' ], [ 4, 5 ] ] ],
            E::from([ 1, 2, 6 => E::from([ 7 => [ 'a' => 'a' ], E::from([ 8 => 4, 5 ]) ]) ])->toListDeep());
    }

    /** @covers \YaLinqo\Enumerable::toDictionary */
    public function testToDictionary(): void {
        // toDictionary ()
        self::assertEquals(
            [],
            E::from([])->toDictionary());
        self::assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toDictionary());
        self::assertEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toDictionary());

        // toDictionary (keySelector)
        self::assertEquals(
            [],
            E::from([])->toDictionary(static fn($v, $k) => $v));
        self::assertEquals(
            [ 1 => 1, 2 => 2, 3 => 3 ],
            E::from([ 1, 2, 3 ])->toDictionary(static fn($v, $k) => $v));
        self::assertEquals(
            [ 1 => 1, 2 => 2, 3 => 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toDictionary(static fn($v, $k) => $v));

        // toDictionary (keySelector, valueSelector)
        self::assertEquals(
            [],
            E::from([])->toDictionary(static fn($v, $k) => $v, static fn($v, $k) => $k));
        self::assertEquals(
            [ 1 => 0, 2 => 1, 3 => 2 ],
            E::from([ 1, 2, 3 ])->toDictionary(static fn($v, $k) => $v, static fn($v, $k) => $k));
        self::assertEquals(
            [ 1 => 0, 2 => 'a', 3 => 1 ],
            E::from([ 1, 'a' => 2, 3 ])->toDictionary(static fn($v, $k) => $v, static fn($v, $k) => $k));
    }

    /** @covers \YaLinqo\Enumerable::toJSON */
    public function testToJSON(): void {
        self::assertEquals(
            '[]',
            E::from([])->toJSON());
        self::assertEquals(
            '[1,2,3]',
            E::from([ 1, 2, 3 ])->toJSON());
        self::assertEquals(
            '{"0":1,"a":2,"1":3}',
            E::from([ 1, 'a' => 2, 3 ])->toJSON());
        self::assertEquals(
            '{"0":1,"1":2,"6":{"7":{"a":"a"},"8":{"8":4,"9":5}}}',
            E::from([ 1, 2, 6 => E::from([ 7 => [ 'a' => 'a' ], E::from([ 8 => 4, 5 ]) ]) ])->toJSON());
    }

    /** @covers \YaLinqo\Enumerable::toLookup */
    public function testToLookup(): void {
        // toLookup ()
        self::assertEquals(
            [],
            E::from([])->toLookup());
        self::assertEquals(
            [ [ 3 ], [ 4 ], [ 5 ] ],
            E::from([ 3, 4, 5 ])->toLookup());
        self::assertEquals(
            [ 'a' => [ 3 ], 'b' => [ 4 ], 'c' => [ 5 ] ],
            E::from([ 'a' => 3, 'b' => 4, 'c' => 5 ])->toLookup());

        // toLookup (keySelector)
        self::assertEquals(
            [ 0 => [ 4, 6, 8 ], 1 => [ 3, 5, 7 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->toLookup(static fn($v, $k) => $v&1));
        self::assertEquals(
            [ 0 => [ 4, 6, 8 ], 1 => [ 3, 5, 7 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->toLookup(static fn($v, $k) => !($k%2)));

        // toLookup (keySelector, valueSelector)
        self::assertEquals(
            [ [ 3 ], [ 5 ], [ 7 ], [ 9 ], [ 11 ], [ 13 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->toLookup(null, static fn($v, $k) => $v + $k));
        self::assertEquals(
            [ 0 => [ 5, 9, 13 ], 1 => [ 3, 7, 11 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->toLookup(static fn($v, $k) => $v&1, static fn($v, $k) => $v + $k));
        self::assertEquals(
            [ 0 => [ 3, 3, 5 ], 1 => [ 3, 3, 4 ] ],
            E::from([ 3, 4, 5, 6, 8, 10 ])->toLookup(static fn($v, $k) => !($k%2), static fn($v, $k) => $v-$k));
    }

    /** @covers \YaLinqo\Enumerable::toKeys */
    public function testToKeys(): void {
        self::assertEnumEquals(
            [],
            E::from([])->toKeys());
        self::assertEnumEquals(
            [ 0, 1, 2 ],
            E::from([ 1, 2, 3 ])->toKeys());
        self::assertEnumEquals(
            [ 0, 'a', 1 ],
            E::from([ 1, 'a' => 2, 3 ])->toKeys());
    }

    /** @covers \YaLinqo\Enumerable::toValues */
    public function testToValues(): void {
        self::assertEnumEquals(
            [],
            E::from([])->toValues());
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toValues());
        self::assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toValues());
    }

    /** @covers \YaLinqo\Enumerable::toObject */
    public function testToObject(): void {
        // toObject
        self::assertEquals(
            new stdClass,
            E::from([])->toObject());
        self::assertEquals(
            (object)[ 'a' => 1, 'b' => true, 'c' => 'd' ],
            E::from([ 'a' => 1, 'b' => true, 'c' => 'd' ])->toObject());

        // toObject (propertySelector)
        $i = 0;
        self::assertEquals(
            (object)[ 'prop1' => 1, 'prop2' => true, 'prop3' => 'd' ],
            E::from([ 'a' => 1, 'b' => true, 'c' => 'd' ])->toObject(function() use (&$i) {
                $i++;
                return "prop$i";
            }));

        // toObject (valueSelector)
        self::assertEquals(
            (object)[ 'propa1' => 'a=1', 'propb1' => 'b=1', 'propcd' => 'c=d' ],
            E::from([ 'a' => 1, 'b' => true, 'c' => 'd' ])->toObject(static fn($v, $k) => "prop$k$v", static fn($v, $k) => "$k=$v"));
    }

    /** @covers \YaLinqo\Enumerable::toString */
    public function testToString(): void {
        // toString ()
        self::assertEquals(
            '',
            E::from([])->toString());
        self::assertEquals(
            '123',
            E::from([ 1, 2, 3 ])->toString());
        self::assertEquals(
            '123',
            E::from([ 1, 'a' => 2, 3 ])->toString());
        self::assertEquals(
            '123',
            E::from([ [ 0, 1 ], [ 0, 2 ], [ 1, 3 ] ])->select(static fn($v, $k) => $v[1], static fn($v, $k) => $v[0])->toString());

        // toString (separator)
        self::assertEquals(
            '',
            E::from([])->toString(', '));
        self::assertEquals(
            '1, 2, 3',
            E::from([ 1, 2, 3 ])->toString(', '));
        self::assertEquals(
            '1, 2, 3',
            E::from([ 1, 'a' => 2, 3 ])->toString(', '));
        self::assertEquals(
            '1, 2, 3',
            E::from([ [ 0, 1 ], [ 0, 2 ], [ 1, 3 ] ])->select(static fn($v, $k) => $v[1], static fn($v, $k) => $v[0])->toString(', '));

        // toString (separator, selector)
        self::assertEquals(
            '',
            E::from([])->toString(', ', static fn($v, $k) => "$k=$v"));
        self::assertEquals(
            '0=1, 1=2, 2=3',
            E::from([ 1, 2, 3 ])->toString(', ', static fn($v, $k) => "$k=$v"));
        self::assertEquals(
            '0=1, a=2, 1=3',
            E::from([ 1, 'a' => 2, 3 ])->toString(', ', static fn($v, $k) => "$k=$v"));
        self::assertEquals(
            '0=1, 0=2, 1=3',
            E::from([ [ 0, 1 ], [ 0, 2 ], [ 1, 3 ] ])->select(static fn($v, $k) => $v[1], static fn($v, $k) => $v[0])->toString(', ', static fn($v, $k) => "$k=$v"));
    }

    // endregion

    // region Actions

    /** @covers \YaLinqo\Enumerable::call */
    public function testCall(): void {
        // call (action)
        $a = [];
        foreach (E::from([])->call(function($v, $k) use (&$a) { $a[$k] = $v; }) as $_) {
        }

        self::assertEquals(
            [],
            $a);
        $a = [];
        foreach (E::from([ 1, 'a' => 2, 3 ])->call(function($v, $k) use (&$a) { $a[$k] = $v; }) as $_){
        }

        self::assertEquals(
            [ 1, 'a' => 2, 3 ],
            $a);
        $a = [];

        foreach (E::from([ 1, 'a' => 2, 3 ])->call(function($v, $k) use (&$a) { $a[$k] = $v; }) as $_) {
            break;
        }
        self::assertEquals(
            [ 1 ],
            $a);
        $a = [];
        E::from([ 1, 'a' => 2, 3 ])->call(function($v, $k) use (&$a) { $a[$k] = $v; });
        self::assertEquals(
            [],
            $a);
    }

    /** @covers \YaLinqo\Enumerable::each */
    public function testEach(): void {
        // call (action)
        $a = [];
        E::from([])->each(function($v, $k) use (&$a) { $a[$k] = $v; });
        self::assertEquals(
            [],
            $a);
        $a = [];
        E::from([ 1, 'a' => 2, 3 ])->each(function($v, $k) use (&$a) { $a[$k] = $v; });
        self::assertEquals(
            [ 1, 'a' => 2, 3 ],
            $a);
    }

    /**
     * @covers \YaLinqo\Enumerable::write
     * @dataProvider dataProvider_testWrite
     */
    public function testWrite(mixed $output, mixed $source, string $separator, mixed $selector): void {
        // toString ()
        $this->expectOutputString($output);
        E::from($source)->write($separator, $selector);
    }

    public function dataProvider_testWrite(): array {
        return [
            // write ()
            [ '', [], '', null ],
            [ '123', [ 1, 2, 3 ], '', null ],
            [ '123', [ 1, 'a' => 2, 3 ], '', null ],
            // write (separator)
            [ '', [], ', ', null ],
            [ '1, 2, 3', [ 1, 2, 3 ], ', ', null ],
            [ '1, 2, 3', [ 1, 'a' => 2, 3 ], ', ', null ],
            // write (separator, selector)
            [ '', [], ', ', static fn($v, $k) => "$k=$v" ],
            [ '0=1, 1=2, 2=3', [ 1, 2, 3 ], ', ', static fn($v, $k) => "$k=$v" ],
            [ '0=1, a=2, 1=3', [ 1, 'a' => 2, 3 ], ', ', static fn($v, $k) => "$k=$v" ],
        ];
    }

    /**
     * @covers \YaLinqo\Enumerable::writeLine
     * @dataProvider dataProvider_testWriteLine
     */
    public function testWriteLine($output, $source, $selector): void {
        // toString ()
        $this->expectOutputString($output);
        E::from($source)->writeLine($selector);
    }

    public function dataProvider_testWriteLine(): array {
        return [
            // writeLine ()
            [ "", [], null ],
            [ "1\n2\n3\n", [ 1, 2, 3 ], null ],
            [ "1\n2\n3\n", [ 1, 'a' => 2, 3 ], null ],
            // writeLine (selector)
            [ "", [], static fn($v, $k) => "$k=$v" ],
            [ "0=1\n1=2\n2=3\n", [ 1, 2, 3 ], static fn($v, $k) => "$k=$v" ],
            [ "0=1\na=2\n1=3\n", [ 1, 'a' => 2, 3 ], static fn($v, $k) => "$k=$v" ],
        ];
    }

    // endregion
}
