<?php

/**
 * @author Alexander Prokhorov
 * @license Simplified BSD
 *
 * @see https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

/** Error messages. */
class Errors {
    public const NO_ELEMENTS = 'Sequence contains no elements.';
    public const NO_MATCHES = 'Sequence contains no matching elements.';
    public const NO_KEY = 'Sequence does not contain the key.';
    public const MANY_ELEMENTS = 'Sequence contains more than one element.';
    public const MANY_MATCHES = 'Sequence contains more than one matching element.';
    public const COUNT_LESS_THAN_ZERO = 'count must be a non-negative value.';
    public const STEP_NEGATIVE = 'step must be a positive value.';
    public const UNSUPPORTED_BUILTIN_TYPE = 'type must be one of built-in types.';
}
