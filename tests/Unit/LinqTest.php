<?php

namespace YaLinqo\Tests\Unit;

use EmptyIterator;
use YaLinqo\Tests\Testing\TestCaseEnumerable;
use YaLinqo\Enumerable;

class LinqTest extends TestCaseEnumerable
{
    public function testFunctions(): void {
        $this->assertInstanceOf(Enumerable::class, from(new EmptyIterator()));
    }
}
