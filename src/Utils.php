<?php

/**
 * Utils class.
 *
 * @author Alexander Prokhorov
 * @license Simplified BSD
 *
 * @see https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * Functions for creating lambdas.
 *
 * @internal
 */
final class Utils {
    public const ERROR_CLOSURE_NULL = 'closure must not be null.';
    public const ERROR_CLOSURE_NOT_CALLABLE = 'closure must be callable';
    public const ERROR_CLOSURE_MOST_NOT_BE_STRING = 'Calling PHP create_function is no longer supported. Please convert the code to an anonymous function.';

    /** Map from comparison functions names to sort flags. Used in lambdaToSortFlagsAndOrder.  @var array */
    private static array $compareFunctionToSortFlags = [
        null => SORT_REGULAR,
        'strcmp' => SORT_STRING,
        'strcasecmp' => 10 /* SORT_STRING | SORT_FLAG_CASE */,
        'strcoll' => SORT_LOCALE_STRING,
        'strnatcmp' => SORT_NATURAL,
        'strnatcasecmp' => 14 /* SORT_NATURAL | SORT_FLAG_CASE */,
    ];

    /**
     * Convert string lambda to callable function. If callable is passed, return as is.
     *
     * @param ?callable             $closure
     * @param callable|Closure|null $default
     *
     * @throws InvalidArgumentException incorrect lambda syntax
     * @throws InvalidArgumentException both closure and default are null
     *
     * @return callable|mixed|null
     */
    public static function createLambda(?callable $closure, mixed $default = null): mixed {
        if ($closure === null) {
            if ($default === null) {
                throw new InvalidArgumentException(self::ERROR_CLOSURE_NULL);
            }

            return $default;
        }

        if ($closure instanceof Closure) {
            return $closure;
        }

        if (is_string($closure)) {
            throw new RuntimeException('Calling PHP create_function is no longer supported. Please convert the code to an anonymous function.');
        }

        if (is_callable($closure)) {
            return $closure;
        }

        throw new InvalidArgumentException(self::ERROR_CLOSURE_NOT_CALLABLE);
    }

    /**
     * Convert string lambda or SORT_ flags to callable function. Sets isReversed to false if descending is reversed.
     *
     * @throws InvalidArgumentException incorrect lambda syntax
     * @throws InvalidArgumentException incorrect SORT_ flags
     */
    public static function createComparer(callable|int|null $closure, int $sortOrder, ?bool &$isReversed): callable|Closure|string|null {
        if ($closure === null) {
            $isReversed = false;

            return $sortOrder === SORT_DESC ? Functions::$compareStrictReversed : Functions::$compareStrict;
        }

        if (is_int($closure)) {
            switch ($closure) {
                case SORT_REGULAR:
                    return Functions::$compareStrict;
                case SORT_NUMERIC:
                    $isReversed = false;

                    return $sortOrder === SORT_DESC ? Functions::$compareIntReversed : Functions::$compareInt;
                case SORT_STRING:
                    return 'strcmp';
                case SORT_STRING | SORT_FLAG_CASE:
                    return 'strcasecmp';
                case SORT_LOCALE_STRING:
                    return 'strcoll';
                case SORT_NATURAL:
                    return 'strnatcmp';
                case SORT_NATURAL | SORT_FLAG_CASE:
                    return 'strnatcasecmp';
                default:
                    throw new InvalidArgumentException("Unexpected sort flag: ${closure}.");
            }
        }

        return $closure;
    }

    /** Convert string lambda to SORT_ flags. Convert sortOrder from bool to SORT_ order. */
    public static function lambdaToSortFlagsAndOrder(callable|int|string|null $closure, bool|int &$sortOrder): callable|int|string|null {
        if ($sortOrder !== SORT_ASC && $sortOrder !== SORT_DESC) {
            $sortOrder = $sortOrder ? SORT_DESC : SORT_ASC;
        }
        if (is_int($closure)) {
            return $closure;
        }

        if (($closure === null || is_string($closure)) && isset(self::$compareFunctionToSortFlags[$closure])) {
            return self::$compareFunctionToSortFlags[$closure];
        }

        return null;
    }
}
