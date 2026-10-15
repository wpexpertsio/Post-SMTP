<?php

namespace PostSMTP\Vendor\GuzzleHttp\Tests\Exception;

use PostSMTP\Vendor\GuzzleHttp\Exception\BadResponseException;
use PostSMTP\Vendor\GuzzleHttp\Exception\RequestException;
use PostSMTP\Vendor\GuzzleHttp\Exception\ServerException;
use PostSMTP\Vendor\GuzzleHttp\Psr7\Request;
use PostSMTP\Vendor\GuzzleHttp\Psr7\Response;
use PostSMTP\Vendor\PHPUnit\Framework\TestCase;
use PostSMTP\Vendor\Psr\Http\Client\NetworkExceptionInterface;
use PostSMTP\Vendor\Psr\Http\Client\RequestExceptionInterface;
/**
 * @covers \GuzzleHttp\Exception\ServerException
 */
class ServerExceptionTest extends TestCase
{
    public function testHasRequestAndResponse()
    {
        $req = new Request('GET', '/');
        $res = new Response(500);
        $prev = new \Exception();
        $e = new ServerException('foo', $req, $res, $prev);
        self::assertInstanceOf(BadResponseException::class, $e);
        self::assertInstanceOf(RequestException::class, $e);
        self::assertInstanceOf(RequestExceptionInterface::class, $e);
        self::assertNotInstanceOf(NetworkExceptionInterface::class, $e);
        self::assertSame($req, $e->getRequest());
        self::assertSame($res, $e->getResponse());
        self::assertTrue($e->hasResponse());
        self::assertSame(500, $e->getCode());
        self::assertSame('foo', $e->getMessage());
        self::assertSame($prev, $e->getPrevious());
    }
}
