<?php

/**
 * @author    Andreas Fischer <bantu@phpbb.com>
 * @copyright 2013 Andreas Fischer
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 */
namespace PostSMTP\Vendor\phpseclib3\Tests\Unit\Crypt\AES;

class McryptTest extends TestCase
{
    protected function setUp()
    {
        $this->engine = 'mcrypt';
    }
}
