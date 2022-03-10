<?php

/**
 * Global functions and initialization.
 *
 * @author Alexander Prokhorov
 * @license Simplified BSD
 *
 * @see https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

use YaLinqo\Enumerable;
use YaLinqo\Functions;

Functions::init();

if (!function_exists('from')) {
    /**
     * Create Enumerable from an array or any other traversable source.
     *
     * @throws Exception|InvalidArgumentException if source is not array or Traversable or Enumerable
     *
     * @see \YaLinqo\Enumerable::from
     */
    function from(IteratorAggregate|Enumerable|Iterator|array $source): YaLinqo\Enumerable {
        return Enumerable::from($source);
    }
}
