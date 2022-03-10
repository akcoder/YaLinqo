<?php

/**
 * EnumerableGeneration trait of Enumerable class.
 *
 * @author Alexander Prokhorov
 * @license Simplified BSD
 *
 * @see https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

use ArrayIterator;
use EmptyIterator;
use Exception;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use Traversable;
use UnexpectedValueException;

/**
 * Trait of {@link Enumerable} containing generation methods.
 */
trait EnumerableGeneration {
    /**
     * Cycles through the source sequence.
     * <p><b>Syntax</b>: cycle (source)
     * <p>Source keys are discarded.
     *
     * @param array|Enumerable|Iterator|IteratorAggregate $source source sequence
     *
     * @throws UnexpectedValueException           if source contains no elements (checked during enumeration)
     * @throws Exception|InvalidArgumentException if source is not array or Traversable or Enumerable
     *
     * @return Enumerable endless list of items repeating the source sequence
     */
    public static function cycle(Enumerable|IteratorAggregate|Iterator|array $source): Enumerable {
        $source = self::from($source);

        return new self(function() use ($source) {
            $isEmpty = true;
            while (true) {
                foreach ($source as $v) {
                    yield $v;
                    $isEmpty = false;
                }

                if ($isEmpty) {
                    throw new UnexpectedValueException(Errors::NO_ELEMENTS);
                }
            }
        });
    }

    /**
     * Returns an empty sequence.
     * <p><b>Syntax</b>: emptyEnum ().
     */
    public static function emptyEnum(): Enumerable {
        return new self(new EmptyIterator(), false);
    }

    /**
     * Converts source into Enumerable sequence.
     * <p><b>Syntax</b>: from (source)
     * <p>Result depends on the type of source:
     * <ul>
     * <li><b>array</b>: Enumerable from ArrayIterator;
     * <li><b>Enumerable</b>: Enumerable source itself;
     * <li><b>Iterator</b>: Enumerable from Iterator;
     * <li><b>IteratorAggregate</b>: Enumerable from Iterator returned from getIterator() method;
     * <li><b>Traversable</b>: Enumerable from the result of foreach over source.
     * </ul>.
     *
     * @param array|Enumerable|int|Iterator|IteratorAggregate|mixed|Traversable $source value to convert into Enumerable sequence
     *
     * @throws Exception|InvalidArgumentException if source is not array or Traversable or Enumerable
     */
    public static function from(mixed $source): Enumerable {
        $it = null;

        if ($source instanceof Enumerable) {
            return $source;
        }

        if (is_array($source)) {
            $it = new ArrayIterator($source);
        } elseif ($source instanceof IteratorAggregate) {
            $it = $source->getIterator();
        } elseif ($source instanceof Traversable) {
            $it = $source;
        }

        if ($it === null) {
            throw new InvalidArgumentException('source must be array or Traversable.');
        }

        return new self($it, false);
    }

    /**
     * Generates a sequence by mimicking a for loop.
     * <p><b>Syntax</b>: generate (funcValue {(v, k) ==> value} [, seedValue [, funcKey {(v, k) ==> key} [, seedKey]]])
     * <p>If seedValue is null, the first value will be the result of calling funcValue on seedValue and seedKey. The same applies for seedKey.
     *
     * @param callable      $funcValue {(v, k) ==> value} State update function to run on value after every iteration of the generator loop. Default: value.
     * @param mixed         $seedValue Initial state of the generator loop for values. Default: null.
     * @param callable|null $funcKey   {(v, k) ==> key} State update function to run on key after every iteration of the generator loop. Default: increment.
     * @param mixed         $seedKey   Initial state of the generator loop ofr keys. Default: 0.
     */
    public static function generate(callable $funcValue, mixed $seedValue = null, ?callable $funcKey = null, mixed $seedKey = null): Enumerable {
        return new self(function() use ($funcValue, $funcKey, $seedValue, $seedKey) {
            $key = $seedKey ?? ($funcKey ? $funcKey($seedValue, $seedKey) : 0);
            $value = $seedValue ?? $funcValue($seedValue, $seedKey);
            yield $key => $value;
            while (true) {
                [$value, $key ] = [
                    $funcValue($value, $key),
                    $funcKey ? $funcKey($value, $key) : $key + 1,
                ];

                yield $key => $value;
            }
        });
    }

    /**
     * Generates a sequence of integral numbers to infinity.
     * <p><b>Syntax</b>: toInfinity ([start [, step]]).
     *
     * @param int $start The first integer in the sequence. Default: 0.
     * @param int $step  The difference between adjacent integers. Default: 1.
     */
    public static function toInfinity(int $start = 0, int $step = 1): Enumerable {
        return new self(function() use ($start, $step) {
            $value = $start - $step;

            while (true) {
                yield $value += $step;
            }
        });
    }

    /**
     * Searches subject for all matches to the regular expression given in pattern and enumerates them in the order specified by flags. After the first match is found, the subsequent searches are continued on from end of the last match.
     * <p><b>Syntax</b>: matches (subject, pattern [, flags]).
     *
     * @param string $subject the input string
     * @param string $pattern the pattern to search for, as a string
     * @param int    $flags   Can be a combination of the following flags: PREG_PATTERN_ORDER, PREG_SET_ORDER, PREG_OFFSET_CAPTURE. Default: PREG_SET_ORDER.
     *
     * @see preg_match_all
     */
    public static function matches(string $subject, string $pattern, int $flags = PREG_SET_ORDER): Enumerable {
        return new self(function() use ($subject, $pattern, $flags) {
            preg_match_all($pattern, $subject, $matches, $flags);

            return $matches !== false ? self::from($matches)->getIterator() : self::emptyEnum();
        });
    }

    /**
     * Generates a sequence of integral numbers to negative infinity.
     * <p><b>Syntax</b>: toNegativeInfinity ([start [, step]]).
     *
     * @param int $start The first integer in the sequence. Default: 0.
     * @param int $step  The difference between adjacent integers. Default: 1.
     */
    public static function toNegativeInfinity(int $start = 0, int $step = 1): Enumerable {
        return self::toInfinity($start, -$step);
    }

    /**
     * Returns a sequence that contains a single element with a specified value.
     * <p><b>Syntax</b>: returnEnum (element).
     *
     * @param mixed $element the single element in the resulting sequence
     *
     * @return Enumerable observable sequence containing the single specified element
     */
    public static function returnEnum(mixed $element): Enumerable {
        return self::repeat($element, 1);
    }

    /**
     * Generates a sequence of integral numbers, beginning with start and containing count elements.
     * <p><b>Syntax</b>: range (start, count [, step])
     * <p>Keys in the generated sequence are sequential: 0, 1, 2 etc.
     * <p>Example: range(3, 4, 2) = 3, 5, 7, 9.
     *
     * @param int $start the value of the first integer in the sequence
     * @param int $count the number of integers to generate
     * @param int $step  The difference between adjacent integers. Default: 1.
     *
     * @return Enumerable a sequence that contains a range of integral numbers
     */
    public static function range(int $start, int $count, int $step = 1): Enumerable {
        if ($count <= 0) {
            return self::emptyEnum();
        }

        return new self(function() use ($start, $count, $step) {
            $value = $start - $step;

            while ($count-- > 0) {
                yield $value += $step;
            }
        });
    }

    /**
     * Generates a reversed sequence of integral numbers, beginning with start and containing count elements.
     * <p><b>Syntax</b>: rangeDown (start, count [, step])
     * <p>Keys in the generated sequence are sequential: 0, 1, 2 etc.
     * <p>Example: rangeDown(9, 4, 2) = 9, 7, 5, 3.
     *
     * @param int $start the value of the first integer in the sequence
     * @param int $count the number of integers to generate
     * @param int $step  The difference between adjacent integers. Default: 1.
     *
     * @return Enumerable a sequence that contains a range of integral numbers
     */
    public static function rangeDown(int $start, int $count, int $step = 1): Enumerable {
        return self::range($start, $count, -$step);
    }

    /**
     * Generates a sequence of integral numbers within a specified range from start to end.
     * <p><b>Syntax</b>: rangeTo (start, end [, step])
     * <p>Keys in the generated sequence are sequential: 0, 1, 2 etc.
     * <p>Example: rangeTo(3, 9, 2) = 3, 5, 7, 9.
     *
     * @param int $start the value of the first integer in the sequence
     * @param int $end   the value of the last integer in the sequence (not included)
     * @param int $step  The difference between adjacent integers. Default: 1.
     *
     * @throws InvalidArgumentException if step is not a positive number
     *
     * @return Enumerable a sequence that contains a range of integral numbers
     */
    public static function rangeTo(int $start, int $end, int $step = 1): Enumerable {
        if ($step <= 0) {
            throw new InvalidArgumentException(Errors::STEP_NEGATIVE);
        }

        return new self(function() use ($start, $end, $step) {
            if ($start <= $end) {
                for ($i = $start; $i < $end; $i += $step) {
                    yield $i;
                }
            } else {
                for ($i = $start; $i > $end; $i -= $step) {
                    yield $i;
                }
            }
        });
    }

    /**
     * Generates a sequence that contains one repeated value.
     * <p><b>Syntax</b>: repeat (element)
     * <p>Generates an endless sequence that contains one repeated value.
     * <p><b>Syntax</b>: repeat (element, count)
     * <p>Generates a sequence of specified length that contains one repeated value.
     * <p>Keys in the generated sequence are sequential: 0, 1, 2 etc.
     *
     * @param mixed $element the value to be repeated
     * @param ?int  $count   The number of times to repeat the value in the generated sequence. Default: null.
     *
     * @throws InvalidArgumentException if count is less than 0
     *
     * @return Enumerable a sequence that contains a repeated value
     */
    public static function repeat(mixed $element, int $count = null): Enumerable {
        if ($count < 0) {
            throw new InvalidArgumentException(Errors::COUNT_LESS_THAN_ZERO);
        }

        return new self(function() use ($element, $count) {
            for ($i = 0; $i < $count || $count === null; ++$i) {
                yield $element;
            }
        });
    }

    /**
     * Split the given string by a regular expression.
     * <p><b>Syntax</b>: split (subject, pattern [, flags]).
     *
     * @param string $subject the input string
     * @param string $pattern the pattern to search for, as a string
     * @param int    $flags   flags can be any combination of the following flags: PREG_SPLIT_NO_EMPTY, PREG_SPLIT_DELIM_CAPTURE, PREG_SPLIT_OFFSET_CAPTURE. Default: 0.
     *
     * @see preg_split
     */
    public static function split(string $subject, string $pattern, int $flags = 0): Enumerable {
        return new self(
            new ArrayIterator(preg_split($pattern, $subject, -1, $flags)),
            false
        );
    }
}
