<?php

/**
 * PKCS#8 Formatted RSA Key Handler
 *
 * PHP version 5
 *
 * Used by PHP's openssl_public_encrypt() and openssl's rsautl (when -pubin is set)
 *
 * Processes keys with the following headers:
 *
 * -----BEGIN ENCRYPTED PRIVATE KEY-----
 * -----BEGIN PRIVATE KEY-----
 * -----BEGIN PUBLIC KEY-----
 *
 * Analogous to ssh-keygen's pkcs8 format (as specified by -m). Although PKCS8
 * is specific to private keys it's basically creating a DER-encoded wrapper
 * for keys. This just extends that same concept to public keys (much like ssh-keygen)
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace PostSMTP\Vendor\phpseclib3\Crypt\RSA\Formats\Keys;

use PostSMTP\Vendor\phpseclib3\Common\Functions\Strings;
use PostSMTP\Vendor\phpseclib3\Crypt\Common\Formats\Keys\PKCS8 as Progenitor;
use PostSMTP\Vendor\phpseclib3\File\ASN1;
use PostSMTP\Vendor\phpseclib3\Math\BigInteger;
/**
 * PKCS#8 Formatted RSA Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PKCS8 extends \PostSMTP\Vendor\phpseclib3\Crypt\Common\Formats\Keys\PKCS8
{
    /**
     * OID Name
     *
     * @var string
     */
    const OID_NAME = 'rsaEncryption';
    /**
     * OID Value
     *
     * @var string
     */
    const OID_VALUE = '1.2.840.113549.1.1.1';
    /**
     * Child OIDs loaded
     *
     * @var bool
     */
    protected static $childOIDsLoaded = \false;
    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password optional
     * @return array
     */
    public static function load($key, $password = '')
    {
        if (!\PostSMTP\Vendor\phpseclib3\Common\Functions\Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . \gettype($key));
        }
        if (\strpos($key, 'PUBLIC') !== \false) {
            $components = ['isPublicKey' => \true];
        } elseif (\strpos($key, 'PRIVATE') !== \false) {
            $components = ['isPublicKey' => \false];
        } else {
            $components = [];
        }
        $key = parent::load($key, $password);
        if (isset($key['privateKey'])) {
            if (!isset($components['isPublicKey'])) {
                $components['isPublicKey'] = \false;
            }
            $type = 'private';
        } else {
            if (!isset($components['isPublicKey'])) {
                $components['isPublicKey'] = \true;
            }
            $type = 'public';
        }
        $result = $components + \PostSMTP\Vendor\phpseclib3\Crypt\RSA\Formats\Keys\PKCS1::load($key[$type . 'Key']);
        if (isset($key['meta'])) {
            $result['meta'] = $key['meta'];
        }
        return $result;
    }
    /**
     * Convert a private key to the appropriate format.
     *
     * @param \phpseclib3\Math\BigInteger $n
     * @param \phpseclib3\Math\BigInteger $e
     * @param \phpseclib3\Math\BigInteger $d
     * @param array $primes
     * @param array $exponents
     * @param array $coefficients
     * @param string $password optional
     * @param array $options optional
     * @return string
     */
    public static function savePrivateKey(\PostSMTP\Vendor\phpseclib3\Math\BigInteger $n, \PostSMTP\Vendor\phpseclib3\Math\BigInteger $e, \PostSMTP\Vendor\phpseclib3\Math\BigInteger $d, array $primes, array $exponents, array $coefficients, $password = '', array $options = [])
    {
        $key = \PostSMTP\Vendor\phpseclib3\Crypt\RSA\Formats\Keys\PKCS1::savePrivateKey($n, $e, $d, $primes, $exponents, $coefficients);
        $key = \PostSMTP\Vendor\phpseclib3\File\ASN1::extractBER($key);
        return self::wrapPrivateKey($key, [], null, $password, null, '', $options);
    }
    /**
     * Convert a public key to the appropriate format
     *
     * @param \phpseclib3\Math\BigInteger $n
     * @param \phpseclib3\Math\BigInteger $e
     * @param array $options optional
     * @return string
     */
    public static function savePublicKey(\PostSMTP\Vendor\phpseclib3\Math\BigInteger $n, \PostSMTP\Vendor\phpseclib3\Math\BigInteger $e, array $options = [])
    {
        $key = \PostSMTP\Vendor\phpseclib3\Crypt\RSA\Formats\Keys\PKCS1::savePublicKey($n, $e);
        $key = \PostSMTP\Vendor\phpseclib3\File\ASN1::extractBER($key);
        return self::wrapPublicKey($key, null);
    }
}
