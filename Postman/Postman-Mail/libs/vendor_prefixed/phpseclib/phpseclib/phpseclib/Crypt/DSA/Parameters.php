<?php

/**
 * DSA Parameters
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace PostSMTP\Vendor\phpseclib3\Crypt\DSA;

use PostSMTP\Vendor\phpseclib3\Crypt\DSA;
/**
 * DSA Parameters
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class Parameters extends \PostSMTP\Vendor\phpseclib3\Crypt\DSA
{
    /**
     * Returns the parameters
     *
     * @param string $type
     * @param array $options optional
     * @return string
     */
    public function toString($type = 'PKCS1', array $options = [])
    {
        $type = self::validatePlugin('Keys', 'PKCS1', 'saveParameters');
        return $type::saveParameters($this->p, $this->q, $this->g, $options);
    }
}
