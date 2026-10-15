<?php

declare (strict_types=1);
namespace PostSMTP\Vendor\GuzzleHttp\Promise\Tests;

use PostSMTP\Vendor\GuzzleHttp\Promise as P;
use PostSMTP\Vendor\GuzzleHttp\Promise\FulfilledPromise;
use PostSMTP\Vendor\GuzzleHttp\Promise\Promise;
use PostSMTP\Vendor\GuzzleHttp\Promise\RejectedPromise;
use PostSMTP\Vendor\PHPUnit\Framework\TestCase;
class EachTest extends TestCase
{
    public function testCallsEachLimit() : void
    {
        $p = new Promise();
        $aggregate = P\Each::ofLimit([$p], 2);
        $p->resolve('a');
        P\Utils::queue()->run();
        $this->assertTrue(P\Is::fulfilled($aggregate));
    }
    public function testEachLimitAllRejectsOnFailure() : void
    {
        $p = [new FulfilledPromise('a'), new RejectedPromise('b')];
        $aggregate = P\Each::ofLimitAll($p, 2);
        P\Utils::queue()->run();
        $this->assertTrue(P\Is::rejected($aggregate));
        $result = P\Utils::inspect($aggregate);
        $this->assertSame('b', $result['reason']);
    }
}
