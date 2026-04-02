<?php

/**
 * Raw RSA Key Handler
 *
 * PHP version 5
 *
 * An array containing two \phpseclib3\Math\BigInteger objects.
 *
 * The exponent can be indexed with any of the following:
 *
 * 0, e, exponent, publicExponent
 *
 * The modulus can be indexed with any of the following:
 *
 * 1, n, modulo, modulus
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace PostSMTP\Vendor\phpseclib3\Crypt\RSA\Formats\Keys;

use PostSMTP\Vendor\phpseclib3\Math\BigInteger;
/**
 * Raw RSA Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Raw {

	/**
	 * Break a public or private key down into its constituent components
	 *
	 * @param string $key
	 * @param string $password optional
	 * @return array
	 */
	public static function load( $key, $password = '' ) {
		if ( ! \is_array( $key ) ) {
			throw new \UnexpectedValueException( 'Key should be a array - not a ' . \gettype( $key ) );
		}
		$key        = \array_change_key_case( $key, \CASE_LOWER );
		$components = array( 'isPublicKey' => \false );
		foreach ( array( 'e', 'exponent', 'publicexponent', 0, 'privateexponent', 'd' ) as $index ) {
			if ( isset( $key[ $index ] ) ) {
				$components['publicExponent'] = $key[ $index ];
				break;
			}
		}
		foreach ( array( 'n', 'modulo', 'modulus', 1 ) as $index ) {
			if ( isset( $key[ $index ] ) ) {
				$components['modulus'] = $key[ $index ];
				break;
			}
		}
		if ( ! isset( $components['publicExponent'] ) || ! isset( $components['modulus'] ) ) {
			throw new \UnexpectedValueException( 'Modulus / exponent not present' );
		}
		if ( isset( $key['primes'] ) ) {
			$components['primes'] = $key['primes'];
		} elseif ( isset( $key['p'] ) && isset( $key['q'] ) ) {
			$indices = array( array( 'p', 'q' ), array( 'prime1', 'prime2' ) );
			foreach ( $indices as $index ) {
				list($i0, $i1) = $index;
				if ( isset( $key[ $i0 ] ) && isset( $key[ $i1 ] ) ) {
					$components['primes'] = array(
						1 => $key[ $i0 ],
						$key[ $i1 ],
					);
				}
			}
		}
		if ( isset( $key['exponents'] ) ) {
			$components['exponents'] = $key['exponents'];
		} else {
			$indices = array( array( 'dp', 'dq' ), array( 'exponent1', 'exponent2' ) );
			foreach ( $indices as $index ) {
				list($i0, $i1) = $index;
				if ( isset( $key[ $i0 ] ) && isset( $key[ $i1 ] ) ) {
					$components['exponents'] = array(
						1 => $key[ $i0 ],
						$key[ $i1 ],
					);
				}
			}
		}
		if ( isset( $key['coefficients'] ) ) {
			$components['coefficients'] = $key['coefficients'];
		} else {
			foreach ( array( 'inverseq', 'q\'', 'coefficient' ) as $index ) {
				if ( isset( $key[ $index ] ) ) {
					$components['coefficients'] = array( 2 => $key[ $index ] );
				}
			}
		}
		if ( ! isset( $components['primes'] ) ) {
			$components['isPublicKey'] = \true;
			return $components;
		}
		if ( ! isset( $components['exponents'] ) ) {
			$one                     = new \PostSMTP\Vendor\phpseclib3\Math\BigInteger( 1 );
			$temp                    = $components['primes'][1]->subtract( $one );
			$exponents               = array( 1 => $components['publicExponent']->modInverse( $temp ) );
			$temp                    = $components['primes'][2]->subtract( $one );
			$exponents[]             = $components['publicExponent']->modInverse( $temp );
			$components['exponents'] = $exponents;
		}
		if ( ! isset( $components['coefficients'] ) ) {
			$components['coefficients'] = array( 2 => $components['primes'][2]->modInverse( $components['primes'][1] ) );
		}
		foreach ( array( 'privateexponent', 'd' ) as $index ) {
			if ( isset( $key[ $index ] ) ) {
				$components['privateExponent'] = $key[ $index ];
				break;
			}
		}
		return $components;
	}
	/**
	 * Convert a private key to the appropriate format.
	 *
	 * @param \phpseclib3\Math\BigInteger $n
	 * @param \phpseclib3\Math\BigInteger $e
	 * @param \phpseclib3\Math\BigInteger $d
	 * @param array                       $primes
	 * @param array                       $exponents
	 * @param array                       $coefficients
	 * @param string                      $password optional
	 * @param array                       $options optional
	 * @return array
	 */
	public static function savePrivateKey( \PostSMTP\Vendor\phpseclib3\Math\BigInteger $n, \PostSMTP\Vendor\phpseclib3\Math\BigInteger $e, \PostSMTP\Vendor\phpseclib3\Math\BigInteger $d, array $primes, array $exponents, array $coefficients, $password = '', array $options = array() ) {
		if ( ! empty( $password ) && \is_string( $password ) ) {
			throw new \PostSMTP\Vendor\phpseclib3\Crypt\RSA\Formats\Keys\UnsupportedFormatException( 'Raw private keys do not support encryption' );
		}
		return array(
			'e'            => clone $e,
			'n'            => clone $n,
			'd'            => clone $d,
			'primes'       => \array_map(
				function ( $var ) {
					return clone $var;
				},
				$primes
			),
			'exponents'    => \array_map(
				function ( $var ) {
					return clone $var;
				},
				$exponents
			),
			'coefficients' => \array_map(
				function ( $var ) {
					return clone $var;
				},
				$coefficients
			),
		);
	}
	/**
	 * Convert a public key to the appropriate format
	 *
	 * @param \phpseclib3\Math\BigInteger $n
	 * @param \phpseclib3\Math\BigInteger $e
	 * @return array
	 */
	public static function savePublicKey( \PostSMTP\Vendor\phpseclib3\Math\BigInteger $n, \PostSMTP\Vendor\phpseclib3\Math\BigInteger $e ) {
		return array(
			'e' => clone $e,
			'n' => clone $n,
		);
	}
}
