<?php

namespace YaLinqo\Tests\Stubs;

// @codeCoverageIgnoreStart

use Iterator;

class AggregateIteratorWrapper implements \IteratorAggregate
{
    private Iterator $iterator;

    /**
     * @param Iterator $iterator
     */
    public function __construct(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    public function getIterator(): Iterator
    {
        return $this->iterator;
    }
}
