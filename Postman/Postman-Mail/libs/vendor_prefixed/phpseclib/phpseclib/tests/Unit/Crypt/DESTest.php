<?php

namespace PostSMTP\Vendor;

/**
 * @author    Andreas Fischer <bantu@phpbb.com>
 * @copyright MMXIII Andreas Fischer
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 */
use PostSMTP\Vendor\phpseclib3\Crypt\DES;
use PostSMTP\Vendor\phpseclib3\Tests\PhpseclibTestCase;
// the AES tests establish the correctness of the modes of operation. this test is inteded to establish the consistency of
// key and iv padding between the multiple engines
class Unit_Crypt_DESTest extends PhpseclibTestCase
{
    public function testEncryptPadding()
    {
        $engines = ['PHP', 'Eval', 'mcrypt', 'OpenSSL'];
        foreach ($engines as $engine) {
            $des = new DES('cbc');
            $des->setKey("d\x00\x00\x00\x00\x00\x00\x00");
            $des->setIV("d\x00\x00\x00\x00\x00\x00\x00");
            if (!$des->isValidEngine($engine)) {
                self::markTestSkipped("Unable to initialize {$engine} engine");
            }
            $des->setPreferredEngine($engine);
            $result = \pack('H*', '3e7613642049af1e');
            $test = $des->encrypt('d');
            $this->assertEquals($result, $test, "Failed asserting that the {$engine} engine produced the correct result");
        }
    }
    public function testDecryptPadding()
    {
        $engines = ['PHP', 'Eval', 'mcrypt', 'OpenSSL'];
        foreach ($engines as $engine) {
            $des = new DES('cbc');
            $des->setKey("\x00\x00\x00\x00\x00\x00\x00\x00");
            $des->setIV("\x00\x00\x00\x00\x00\x00\x00\x00");
            $des->disablePadding();
            if (!$des->isValidEngine($engine)) {
                self::markTestSkipped("Unable to initialize {$engine} engine");
            }
            $des->setPreferredEngine($engine);
            $result = \pack('H*', '79b305d1ce555221');
            $test = $des->decrypt("d\x00\x00\x00\x00\x00\x00\x00");
            $this->assertEquals($result, $test, "Failed asserting that the {$engine} engine produced the correct result");
        }
    }
}
