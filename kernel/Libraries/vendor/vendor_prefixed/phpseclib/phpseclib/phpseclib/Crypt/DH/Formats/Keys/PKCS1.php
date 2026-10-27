<?php

/**
 * "PKCS1" Formatted EC Key Handler
 *
 * PHP version 5
 *
 * Processes keys with the following headers:
 *
 * -----BEGIN DH PARAMETERS-----
 *
 * Technically, PKCS1 is for RSA keys, only, but we're using PKCS1 to describe
 * DSA, whose format isn't really formally described anywhere, so might as well
 * use it to describe this, too.
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace PostSMTP\Vendor\phpseclib3\Crypt\DH\Formats\Keys;

use PostSMTP\Vendor\phpseclib3\Crypt\Common\Formats\Keys\PKCS1 as Progenitor;
use PostSMTP\Vendor\phpseclib3\File\ASN1;
use PostSMTP\Vendor\phpseclib3\File\ASN1\Maps;
use PostSMTP\Vendor\phpseclib3\Math\BigInteger;
/**
 * "PKCS1" Formatted DH Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PKCS1 extends \PostSMTP\Vendor\phpseclib3\Crypt\Common\Formats\Keys\PKCS1
{
    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password optional
     * @return array
     */
    public static function load($key, $password = '')
    {
        $key = parent::load($key, $password);
        $decoded = \PostSMTP\Vendor\phpseclib3\File\ASN1::decodeBER($key);
        if (!$decoded) {
            throw new \RuntimeException('Unable to decode BER');
        }
        $components = \PostSMTP\Vendor\phpseclib3\File\ASN1::asn1map($decoded[0], \PostSMTP\Vendor\phpseclib3\File\ASN1\Maps\DHParameter::MAP);
        if (!\is_array($components)) {
            throw new \RuntimeException('Unable to perform ASN1 mapping on parameters');
        }
        return $components;
    }
    /**
     * Convert EC parameters to the appropriate format
     *
     * @return string
     */
    public static function saveParameters(\PostSMTP\Vendor\phpseclib3\Math\BigInteger $prime, \PostSMTP\Vendor\phpseclib3\Math\BigInteger $base, array $options = [])
    {
        $params = ['prime' => $prime, 'base' => $base];
        $params = \PostSMTP\Vendor\phpseclib3\File\ASN1::encodeDER($params, \PostSMTP\Vendor\phpseclib3\File\ASN1\Maps\DHParameter::MAP);
        return "-----BEGIN DH PARAMETERS-----\r\n" . \chunk_split(\base64_encode($params), 64) . "-----END DH PARAMETERS-----\r\n";
    }
}
