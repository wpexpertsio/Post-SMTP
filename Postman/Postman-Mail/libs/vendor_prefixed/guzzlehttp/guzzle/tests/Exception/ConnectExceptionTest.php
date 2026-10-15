<?php

namespace PostSMTP\Vendor\GuzzleHttp\Tests\Exception;

use PostSMTP\Vendor\GuzzleHttp\Exception\ConnectException;
use PostSMTP\Vendor\GuzzleHttp\Psr7\Request;
use PostSMTP\Vendor\PHPUnit\Framework\TestCase;
use PostSMTP\Vendor\Psr\Http\Client\NetworkExceptionInterface;
use PostSMTP\Vendor\Psr\Http\Client\RequestExceptionInterface;
/**
 * @covers \GuzzleHttp\Exception\ConnectException
 */
class ConnectExceptionTest extends TestCase
{
    public function testHasRequest()
    {
        $req = new Request('GET', '/');
        $prev = new \Exception();
        $e = new ConnectException('foo', $req, $prev, ['foo' => 'bar']);
        self::assertInstanceOf(NetworkExceptionInterface::class, $e);
        self::assertNotInstanceOf(RequestExceptionInterface::class, $e);
        self::assertSame($req, $e->getRequest());
        self::assertSame('foo', $e->getMessage());
        self::assertSame('bar', $e->getHandlerContext()['foo']);
        self::assertSame($prev, $e->getPrevious());
    }
}
