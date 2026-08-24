<?php

/**
 * Raw Signature Handler
 *
 * PHP version 5
 *
 * Handles signatures as arrays
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace PostSMTP\Vendor\phpseclib3\Crypt\Common\Formats\Signature;

use PostSMTP\Vendor\phpseclib3\Math\BigInteger;
/**
 * Raw Signature Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Raw
{
    /**
     * Loads a signature
     *
     * @param array $sig
     * @return array|bool
     */
    public static function load($sig)
    {
        switch (\true) {
            case !\is_array($sig):
            case !isset($sig['r']) || !isset($sig['s']):
            case !$sig['r'] instanceof \PostSMTP\Vendor\phpseclib3\Math\BigInteger:
            case !$sig['s'] instanceof \PostSMTP\Vendor\phpseclib3\Math\BigInteger:
                return \false;
        }
        return ['r' => $sig['r'], 's' => $sig['s']];
    }
    /**
     * Returns a signature in the appropriate format
     *
     * @param \phpseclib3\Math\BigInteger $r
     * @param \phpseclib3\Math\BigInteger $s
     * @return string
     */
    public static function save(\PostSMTP\Vendor\phpseclib3\Math\BigInteger $r, \PostSMTP\Vendor\phpseclib3\Math\BigInteger $s)
    {
        return \compact('r', 's');
    }
}
