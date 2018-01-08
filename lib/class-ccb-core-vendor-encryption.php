<?php
/**
 * A class to handle secure encryption and decryption of arbitrary data
 *
 * @link       https://www.wpccb.com
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/lib
 */

/**
 * A class to handle secure encryption and decryption of arbitrary data
 *
 * Note that this is not just straight encryption.  It also has a few other
 * features in it to make the encrypted data far more secure.  Note that any
 * other implementations used to decrypt data will have to do the same exact
 * operations.
 *
 * Security Benefits:
 *
 * - Uses Key stretching
 * - Hides the Initialization Vector
 * - Does HMAC verification of source data
 */
class CCB_Core_Vendor_Encryption {

	/**
	 * The mcrypt cipher to use for this instance
	 *
	 * @var string $cipher
	 */
	protected $cipher = '';

	/**
	 * The mcrypt cipher mode to use
	 *
	 * @var int $mode
	 */
	protected $mode = '';

	/**
	 * The number of rounds to feed into PBKDF2 for key generation
	 *
	 * @var int $rounds
	 */
	protected $rounds = 100;

	/**
	 * Constructor!
	 *
	 * @param string $cipher The MCRYPT_* cypher to use for this instance.
	 * @param int    $mode   The MCRYPT_MODE_* mode to use for this instance.
	 * @param int    $rounds The number of PBKDF2 rounds to do on the key.
	 */
	public function __construct( $cipher, $mode, $rounds = 100 ) {
		$this->cipher = $cipher;
		$this->mode = $mode;
		$this->rounds = (int) $rounds;
	}

	/**
	 * Decrypt the data with the provided key
	 *
	 * @param string $data The encrypted datat to decrypt.
	 * @param string $key  The key to use for decryption.
	 *
	 * @returns string|false The returned string if decryption is successful false if it is not
	 */
	public function decrypt( $data, $key ) {
		$salt = substr( $data, 0, 128 );
		$enc = substr( $data, 128, -64 );
		$mac = substr( $data, -64 );

		list ( $cipher_key, $mac_key, $iv ) = $this->get_keys( $salt, $key );

		if ( hash_hmac( 'sha512', $enc, $mac_key, true ) !== $mac ) {
			return false;
		}

		$dec = mcrypt_decrypt( $this->cipher, $cipher_key, $enc, $this->mode, $iv );

		$data = $this->unpad( $dec );

		return $data;
	}

	/**
	 * Encrypt the supplied data using the supplied key
	 *
	 * @param string $data The data to encrypt.
	 * @param string $key  The key to encrypt with.
	 *
	 * @returns string The encrypted data
	 */
	public function encrypt( $data, $key ) {
		$salt = mcrypt_create_iv( 128, MCRYPT_RAND );
		list ( $cipher_key, $mac_key, $iv ) = $this->get_keys( $salt, $key );

		$data = $this->pad( $data );

		$enc = mcrypt_encrypt( $this->cipher, $cipher_key, $data, $this->mode, $iv );

		$mac = hash_hmac( 'sha512', $enc, $mac_key, true );
		return $salt . $enc . $mac;
	}

	/**
	 * Generates a set of keys given a random salt and a master key
	 *
	 * @param string $salt A random string to change the keys each encryption.
	 * @param string $key  The supplied key to encrypt with.
	 *
	 * @returns array An array of keys ( a cipher key, a mac key, and a IV )
	 */
	protected function get_keys( $salt, $key ) {
		if ( function_exists( 'mcrypt_get_iv_size' ) ) {
			$iv_size = mcrypt_get_iv_size( $this->cipher, $this->mode );
			$key_size = mcrypt_get_key_size( $this->cipher, $this->mode );
			$length = 2 * $key_size + $iv_size;

			$key = $this->pbkdf2( 'sha512', $key, $salt, $this->rounds, $length );

			$cipher_key = substr( $key, 0, $key_size );
			$mac_key = substr( $key, $key_size, $key_size );
			$iv = substr( $key, 2 * $key_size );
			return [ $cipher_key, $mac_key, $iv ];
		} else {
			return false;
		}
	}

	/**
	 * Stretch the key using the PBKDF2 algorithm
	 *
	 * @see http://en.wikipedia.org/wiki/PBKDF2
	 *
	 * @param string $algo   The algorithm to use.
	 * @param string $key    The key to stretch.
	 * @param string $salt   A random salt.
	 * @param int    $rounds The number of rounds to derive.
	 * @param int    $length The length of the output key.
	 *
	 * @returns string The derived key.
	 */
	protected function pbkdf2( $algo, $key, $salt, $rounds, $length ) {
		$size   = strlen( hash( $algo, '', true ) );
		$len    = ceil( $length / $size );
		$result = '';
		for ( $i = 1; $i <= $len; $i++ ) {
			$tmp = hash_hmac( $algo, $salt . pack( 'N', $i ), $key, true );
			$res = $tmp;
			for ( $j = 1; $j < $rounds; $j++ ) {
				$tmp  = hash_hmac( $algo, $tmp, $key, true );
				$res ^= $tmp;
			}
			$result .= $res;
		}
		return substr( $result, 0, $length );
	}

	/**
	 * Add padding based on the block size
	 *
	 * @param string $data The data to encrypt.
	 * @return string
	 */
	protected function pad( $data ) {
		$length = mcrypt_get_block_size( $this->cipher, $this->mode );
		$pad_amount = $length - strlen( $data ) % $length;
		if ( 0 === $pad_amount ) {
			$pad_amount = $length;
		}
		return $data . str_repeat( chr( $pad_amount ), $pad_amount );
	}

	/**
	 * Remove padding based on the block size
	 *
	 * @param string $data The data to decrypt.
	 * @return string
	 */
	protected function unpad( $data ) {
		$length = mcrypt_get_block_size( $this->cipher, $this->mode );
		$last = ord( $data[ strlen( $data ) - 1 ] );
		if ( $last > $length ) {
			 return false;
		}
		if ( substr( $data, -1 * $last ) !== str_repeat( chr( $last ), $last ) ) {
			return false;
		}
		return substr( $data, 0, -1 * $last );
	}
}
