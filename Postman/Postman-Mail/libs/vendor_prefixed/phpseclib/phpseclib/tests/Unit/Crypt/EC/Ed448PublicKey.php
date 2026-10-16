<?php

namespace PostSMTP\Vendor\phpseclib3\Tests\Unit\Crypt\EC;

use PostSMTP\Vendor\phpseclib3\Common\Functions\Strings;
use PostSMTP\Vendor\phpseclib3\Crypt\EC\Curves\Ed448;
use PostSMTP\Vendor\phpseclib3\Crypt\EC\Formats\Keys\Common;
class Ed448PublicKey
{
    use Common;
    public static function load($key, $password = '')
    {
        if (!Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . \gettype($key));
        }
        if (\strlen($key) != 57) {
            throw new \LengthException('Key length should be 57 bytes');
        }
        $components = ['curve' => new Ed448()];
        $components['QA'] = self::extractPoint($key, $components['curve']);
        return $components;
    }
}
