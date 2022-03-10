<?php

namespace YaLinqo\Tests\Unit;

use YaLinqo\Enumerable as E;
use YaLinqo\Tests\Testing\TestCaseEnumerable;

/** @covers \YaLinqo\OrderedEnumerable
 */
class OrderedEnumerableTest extends TestCaseEnumerable
{
    public function testThenByDir_asc(): void {
        // thenByDir (false)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenByDir(false));
        self::assertEnumValuesEquals(
            [ 1, 11, 2, 22, 22, 333, 444 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenByDir(false));
        self::assertEnumValuesEquals(
            [ 444, 333, 2, 22, 22, 1, 11 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, static fn($a, $b) => -strncmp($a,$b,1))->thenByDir(false));

        // thenByDir (false, keySelector)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy()->thenByDir(false, static fn($v, $k) => $v-$k));
        self::assertEnumValuesEquals(
            [ [ 0, 4 ], [ 1 ], [ 1, 0, 0, 2 ], [ 1, 0, 3 ] ],
            E::from([ [ 1 ], [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ] ])->orderBy(static fn($v, $k) => $v[0])->thenByDir(false, static fn($v, $k) => $v[$k]));

        // thenByDir (false, keySelector, comparer)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenByDir(false, null, static fn($a, $b) => strncmp($a,$b,1)));
        self::assertEnumValuesEquals(
            [ 22, 2, 444, 11, 22, 1, 333 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])
                ->orderBy(static fn($v, $k) => (int)(-$k/2))->thenByDir(false, null, static fn($a, $b) => strncmp($a,$b,1)));
    }

    public function testThenByDir_desc(): void {
        // thenByDir (true)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenByDir(true));
        self::assertEnumValuesEquals(
            [ 11, 1, 22, 22, 2, 333, 444 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenByDir(true));
        self::assertEnumValuesEquals(
            [ 444, 333, 22, 22, 2, 11, 1 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, static fn($a, $b) => -strncmp($a,$b,1))->thenByDir(true));

        // thenByDir (true, keySelector)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy()->thenByDir(true, static fn($v, $k) => $v-$k));
        self::assertEnumValuesEquals(
            [ [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ], [ 1 ] ],
            E::from([ [ 1 ], [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ] ])->orderBy(static fn($v, $k) => $v[0])->thenByDir(true, static fn($v, $k) => $v[$k]));

        // thenByDir (true, keySelector, comparer)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenByDir(true, null, static fn($a, $b) => strncmp($a,$b,1)));
        self::assertEnumValuesEquals(
            [ 333, 1, 22, 11, 444, 2, 22 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])
                ->orderBy(static fn($v, $k) => (int)($k/2))->thenByDir(true, null, static fn($a, $b) => strncmp($a,$b,1)));
    }

    public function testThenBy(): void {
        // thenBy ()
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenBy());
        self::assertEnumValuesEquals(
            [ 1, 11, 2, 22, 22, 333, 444 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenBy());
        self::assertEnumValuesEquals(
            [ 444, 333, 2, 22, 22, 1, 11 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, static fn($a, $b) => -strncmp($a,$b,1))->thenBy());

        // thenBy (keySelector)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy()->thenBy(static fn($v, $k) => $v-$k));
        self::assertEnumValuesEquals(
            [ [ 0, 4 ], [ 1 ], [ 1, 0, 0, 2 ], [ 1, 0, 3 ] ],
            E::from([ [ 1 ], [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ] ])->orderBy(static fn($v, $k) => $v[0])->thenBy(static fn($v, $k) => $v[$k]));

        // thenBy (keySelector, comparer)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenBy(null, static fn($a, $b) => strncmp($a,$b,1)));
        self::assertEnumValuesEquals(
            [ 22, 2, 444, 11, 22, 1, 333 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])
                ->orderBy(static fn($v, $k) => (int)(-$k/2))->thenBy(null, static fn($a, $b) => strncmp($a,$b,1)));
    }

    public function testThenByDescending(): void {
        // thenByDescending ()
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenByDescending());
        self::assertEnumValuesEquals(
            [ 11, 1, 22, 22, 2, 333, 444 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenByDescending());
        self::assertEnumValuesEquals(
            [ 444, 333, 22, 22, 2, 11, 1 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, static fn($a, $b) => -strncmp($a,$b,1))->thenByDescending());

        // thenByDescending (keySelector)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy()->thenByDescending(static fn($v, $k) => $v-$k));
        self::assertEnumValuesEquals(
            [ [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ], [ 1 ] ],
            E::from([ [ 1 ], [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ] ])->orderBy(static fn($v, $k) => $v[0])->thenByDescending(static fn($v, $k) => $v[$k]));

        // thenByDescending (keySelector, comparer)
        self::assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, static fn($a, $b) => strncmp($a,$b,1))->thenByDescending(null, static fn($a, $b) => strncmp($a,$b,1)));
        self::assertEnumValuesEquals(
            [ 333, 1, 22, 11, 444, 2, 22 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])
                ->orderBy(static fn($v, $k) => (int)($k/2))->thenByDescending(null, static fn($a, $b) => strncmp($a,$b,1)));
    }

    public function testThenByAll_multiple(): void {
        $a = [];
        for ($i = 0; $i < 2; ++$i) {
            for ($j = 0; $j < 2; ++$j) {
                for ($k = 0; $k < 2; ++$k) {
                    $a[] = [$i, $j, $k ];
                }
            }
        }

        shuffle($a);

        $this->assertBinArrayEquals(
            [ '000', '001', '010', '011', '100', '101', '110', '111' ],
            E::from($a)->orderBy(static fn($v, $k) => $v[0])->thenBy(static fn($v, $k) => $v[1])->thenBy(static fn($v, $k) => $v[2]));
        $this->assertBinArrayEquals(
            [ '001', '000', '011', '010', '101', '100', '111', '110' ],
            E::from($a)->orderBy(static fn($v, $k) => $v[0])->thenBy(static fn($v, $k) => $v[1])->thenByDescending(static fn($v, $k) => $v[2]));
        $this->assertBinArrayEquals(
            [ '010', '011', '000', '001', '110', '111', '100', '101' ],
            E::from($a)->orderBy(static fn($v, $k) => $v[0])->thenByDescending(static fn($v, $k) => $v[1])->thenBy(static fn($v, $k) => $v[2]));
        $this->assertBinArrayEquals(
            [ '011', '010', '001', '000', '111', '110', '101', '100' ],
            E::from($a)->orderBy(static fn($v, $k) => $v[0])->thenByDescending(static fn($v, $k) => $v[1])->thenByDescending(static fn($v, $k) => $v[2]));
        $this->assertBinArrayEquals(
            [ '100', '101', '110', '111', '000', '001', '010', '011' ],
            E::from($a)->orderByDescending(static fn($v, $k) => $v[0])->thenBy(static fn($v, $k) => $v[1])->thenBy(static fn($v, $k) => $v[2]));
        $this->assertBinArrayEquals(
            [ '101', '100', '111', '110', '001', '000', '011', '010' ],
            E::from($a)->orderByDescending(static fn($v, $k) => $v[0])->thenBy(static fn($v, $k) => $v[1])->thenByDescending(static fn($v, $k) => $v[2]));
        $this->assertBinArrayEquals(
            [ '110', '111', '100', '101', '010', '011', '000', '001' ],
            E::from($a)->orderByDescending(static fn($v, $k) => $v[0])->thenByDescending(static fn($v, $k) => $v[1])->thenBy(static fn($v, $k) => $v[2]));
        $this->assertBinArrayEquals(
            [ '111', '110', '101', '100', '011', '010', '001', '000' ],
            E::from($a)->orderByDescending(static fn($v, $k) => $v[0])->thenByDescending(static fn($v, $k) => $v[1])->thenByDescending(static fn($v, $k) => $v[2]));
    }

    public function assertBinArrayEquals(array $expected, E $actual): void {
        $this->assertEquals($expected, $actual->select(static fn($v, $k) => implode($v))->toList());
    }
}
