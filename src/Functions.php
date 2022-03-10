<?php

/**
 * Functions class.
 *
 * @author Alexander Prokhorov
 * @license Simplified BSD
 *
 * @see https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

/**
 * Container for standard functions in the form of closures.
 */
class Functions {
    /**
     * Identity function: returns the only argument.
     *
     * @var callable {(x) ==> x}
     */
    public static $identity;
    /**
     * Key function: returns the second argument of two.
     *
     * @var callable {(v, k) ==> k}
     */
    public static $key;
    /**
     * Value function: returns the first argument of two.
     *
     * @var callable {(v, k) ==> v}
     */
    public static $value;
    /**
     * True function: returns true.
     *
     * @var callable {() ==> true}
     */
    public static $true;
    /**
     * False function: returns false.
     *
     * @var callable {() ==> false}
     */
    public static $false;
    /**
     * Blank function: does nothing.
     *
     * @var callable {() ==> {}}
     */
    public static $blank;
    /**
     * Compare strict function: returns -1, 0 or 1 based on === and > operators.
     *
     * @var callable
     */
    public static $compareStrict;
    /**
     * Compare strict function reversed: returns 1, 0 or -1 based on === and > operators.
     *
     * @var callable
     */
    public static $compareStrictReversed;
    /**
     * Compare loose function: returns -1, 0 or 1 based on == and > operators.
     *
     * @var callable
     */
    public static $compareLoose;
    /**
     * Compare loose function reversed: returns 1, 0 or -1 based on == and > operators.
     *
     * @var callable
     */
    public static $compareLooseReversed;
    /**
     * Compare int function: returns the difference between the first and the second argument.
     *
     * @var callable
     */
    public static $compareInt;
    /**
     * Compare int function reversed: returns the difference between the second and the first argument.
     *
     * @var callable
     */
    public static $compareIntReversed;

    /**
     * @internal
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function init(): void {
        self::$identity = static fn($x) => $x;

        self::$key = static fn($v, $k) => $k;

        self::$value = static fn($v, $k) => $v;

        self::$true = static fn() => true;

        self::$false = static fn() => false;

        self::$blank = static function() { };

        self::$compareStrict = static function($a, $b) {
            if ($a === $b) {
                return 0;
            }
            if ($a > $b) {
                return 1;
            }

            return -1;
        };

        self::$compareStrictReversed = static function($a, $b) {
            if ($a === $b) {
                return 0;
            }
            if ($a > $b) {
                return -1;
            }

            return 1;
        };

        self::$compareLoose = static function($a, $b) {
            if ($a == $b) {
                return 0;
            }
            if ($a > $b) {
                return 1;
            }

            return -1;
        };

        self::$compareLooseReversed = static function($a, $b) {
            if ($a == $b) {
                return 0;
            }
            if ($a > $b) {
                return -1;
            }

            return 1;
        };

        self::$compareInt = static fn($a, $b) => $a - $b;

        self::$compareIntReversed = static fn($a, $b) => $b - $a;
    }

    /**
     * Increment function: returns incremental integers starting from 0.
     *
     * @return callable|\Closure
     */
    public static function increment(): callable|\Closure {
        $i = 0;

        return static function() use (&$i) { return $i++; };
    }
}
