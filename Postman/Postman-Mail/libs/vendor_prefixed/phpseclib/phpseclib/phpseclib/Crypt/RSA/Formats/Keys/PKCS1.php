<?php

/**
 * PKCS#1 Formatted RSA Key Handler
 *
 * PHP version 5
 *
 * Used by File/X509.php
 *
 * Processes keys with the following headers:
 *
 * -----BEGIN RSA PRIVATE KEY-----
 * -----BEGIN RSA PUBLIC KEY-----
 *
 * Analogous to ssh-keygen's pem format (as specified by -m)
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace PostSMTP\Vendor\phpseclib3\Crypt\RSA\Formats\Keys;

use PostSMTP\Vendor\phpseclib3\Common\Functions\Strings;
use PostSMTP\Vendor\phpseclib3\Crypt\Common\Formats\Keys\PKCS1 as Progenitor;
use PostSMTP\Vendor\phpseclib3\File\ASN1;
use PostSMTP\Vendor\phpseclib3\File\ASN1\Maps;
use PostSMTP\Vendor\phpseclib3\Math\BigInteger;
/**
 * PKCS#1 Formatted RSA Key Handler
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
        $decoded = \PostSMTP\Vendor\phpseclib3\File\ASN1::decodeBER($key);
        if (!$decoded) {
            throw new \RuntimeException('Unable to decode BER');
        }
        $key = \PostSMTP\Vendor\phpseclib3\File\ASN1::asn1map($decoded[0], \PostSMTP\Vendor\phpseclib3\File\ASN1\Maps\RSAPrivateKey::MAP);
        if (\is_array($key)) {
            $components += ['modulus' => $key['modulus'], 'publicExponent' => $key['publicExponent'], 'privateExponent' => $key['privateExponent'], 'primes' => [1 => $key['prime1'], $key['prime2']], 'exponents' => [1 => $key['exponent1'], $key['exponent2']], 'coefficients' => [2 => $key['coefficient']]];
            if ($key['version'] == 'multi') {
                foreach ($key['otherPrimeInfos'] as $primeInfo) {
                    $components['primes'][] = $primeInfo['prime'];
                    $components['exponents'][] = $primeInfo['exponent'];
                    $components['coefficients'][] = $primeInfo['coefficient'];
                }
            }
            if (!isset($components['isPublicKey'])) {
                $components['isPublicKey'] = \false;
            }
            return $components;
        }
        $key = \PostSMTP\Vendor\phpseclib3\File\ASN1::asn1map($decoded[0], \PostSMTP\Vendor\phpseclib3\File\ASN1\Maps\RSAPublicKey::MAP);
        if (!\is_array($key)) {
            throw new \RuntimeException('Unable to perform ASN1 mapping');
        }
        if (!isset($components['isPublicKey'])) {
            $components['isPublicKey'] = \true;
        }
        return $components + $key;
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
        $num_primes = \count($primes);
        $key = ['version' => $num_primes == 2 ? 'two-prime' : 'multi', 'modulus' => $n, 'publicExponent' => $e, 'privateExponent' => $d, 'prime1' => $primes[1], 'prime2' => $primes[2], 'exponent1' => $exponents[1], 'exponent2' => $exponents[2], 'coefficient' => $coefficients[2]];
        for ($i = 3; $i <= $num_primes; $i++) {
            $key['otherPrimeInfos'][] = ['prime' => $primes[$i], 'exponent' => $exponents[$i], 'coefficient' => $coefficients[$i]];
        }
        $key = \PostSMTP\Vendor\phpseclib3\File\ASN1::encodeDER($key, \PostSMTP\Vendor\phpseclib3\File\ASN1\Maps\RSAPrivateKey::MAP);
        return self::wrapPrivateKey($key, 'RSA', $password, $options);
    }
    /**
     * Convert a public key to the appropriate format
     *
     * @param \phpseclib3\Math\BigInteger $n
     * @param \phpseclib3\Math\BigInteger $e
     * @return string
     */
    public static function savePublicKey(\PostSMTP\Vendor\phpseclib3\Math\BigInteger $n, \PostSMTP\Vendor\phpseclib3\Math\BigInteger $e)
    {
        $key = ['modulus' => $n, 'publicExponent' => $e];
        $key = \PostSMTP\Vendor\phpseclib3\File\ASN1::encodeDER($key, \PostSMTP\Vendor\phpseclib3\File\ASN1\Maps\RSAPublicKey::MAP);
        return self::wrapPublicKey($key, 'RSA');
    }
}
