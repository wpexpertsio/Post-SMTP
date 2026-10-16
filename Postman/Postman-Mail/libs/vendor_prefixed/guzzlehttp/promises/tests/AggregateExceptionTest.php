<?php

declare (strict_types=1);
namespace PostSMTP\Vendor\GuzzleHttp\Promise\Tests;

use PostSMTP\Vendor\GuzzleHttp\Promise\AggregateException;
use PostSMTP\Vendor\PHPUnit\Framework\TestCase;
class AggregateExceptionTest extends TestCase
{
    public function testHasReason() : void
    {
        $e = new AggregateException('foo', ['baz', 'bar']);
        $this->assertStringContainsString('foo', $e->getMessage());
        $this->assertSame(['baz', 'bar'], $e->getReason());
    }
}
