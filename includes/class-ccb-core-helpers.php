<?php
/**
 * Static class for all plugin files to access
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Used to store helpful properties and
 * define some helpful utility methods
 *
 * @since      1.0.0
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Helpers {

	const SYNC_STATUS_KEY = 'ccb-core-sync-in-progress';

	/**
	 * Instance of the Helper class
	 *
	 * @var CCB_Core_Helpers
	 * @access protected
	 * @static
	 */
	private static $instance;

	/**
	 * The options set by the user
	 *
	 * @var array
	 */
	private $plugin_options = array();

	/**
	 * Unused constructor in the singleton pattern
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// Initialize this class with the instance() method.
	}

	/**
	 * Returns the instance of the class
	 *
	 * @access public
	 * @static
	 * @return CCB_Core_Helpers
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new CCB_Core_Helpers();
			static::$instance->setup();
		}
		return static::$instance;
	}

	/**
	 * Initial setup of the singleton
	 *
	 * @access private
	 * @return void
	 */
	private function setup() {
		// Get any options the user may have set.
		$this->plugin_options = get_option( 'ccb_core' );
	}

	/**
	 * Get any options stored by the user
	 *
	 * @return array
	 */
	public function get_options() {
		return $this->plugin_options;
	}

	/**
	 * Encrypts and base64_encodes a string safe for serialization in WordPress
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param    string $data The data to be encrypted.
	 * @return   string
	 */
	public function encrypt( $data ) {

		$encrypted_value = false;
		$key = wp_salt() . md5( 'ccb-core' );

		if ( ! empty( $data ) ) {
			try {
				$e = new CCB_Core_Vendor_Encryption( MCRYPT_BlOWFISH, MCRYPT_MODE_CBC );
				$encrypted_value = base64_encode( $e->encrypt( $data, $key ) );
			} catch ( Exception $ex ) {
				// TODO: Better exception handling.
			}

		}

		return $encrypted_value;
	}

	/**
	 * Decrypts and base64_decodes a string
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param    string $data The data to be decrypted.
	 * @return   string
	 */
	public function decrypt( $data ) {

		$decrypted_value = false;
		$key = wp_salt() . md5( 'ccb-core' );

		if ( ! empty( $data ) ) {
			try {
				$e = new CCB_Core_Vendor_Encryption( MCRYPT_BlOWFISH, MCRYPT_MODE_CBC );
				$decrypted_value = $e->decrypt( base64_decode( $data ), $key );
			} catch ( Exception $ex ) {
				// TODO: Better exception handling.
			}

		}

		return $decrypted_value;
	}

}
