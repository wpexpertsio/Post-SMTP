<?php

namespace PostSMTP\Vendor\GuzzleHttp\Tests\Exception;

use PostSMTP\Vendor\GuzzleHttp\Exception\GuzzleException;
use PostSMTP\Vendor\GuzzleHttp\Exception\TransferException;
use PostSMTP\Vendor\PHPUnit\Framework\TestCase;
use PostSMTP\Vendor\Psr\Http\Client\ClientExceptionInterface;
/**
 * @covers \GuzzleHttp\Exception\TransferException
 */
class TransferExceptionTest extends TestCase
{
    public function testIsGuzzleException()
    {
        $prev = new \Exception();
        $e = new TransferException('foo', 123, $prev);
        self::assertInstanceOf(\RuntimeException::class, $e);
        self::assertInstanceOf(GuzzleException::class, $e);
        self::assertInstanceOf(ClientExceptionInterface::class, $e);
        self::assertSame('foo', $e->getMessage());
        self::assertSame(123, $e->getCode());
        self::assertSame($prev, $e->getPrevious());
    }
}
